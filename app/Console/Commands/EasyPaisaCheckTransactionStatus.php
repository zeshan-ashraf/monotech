<?php

namespace App\Console\Commands;

use App\Helpers\ProcessHelper;
use App\Models\{Setting, SurplusAmount, Transaction, User};
use App\Service\StatusService;
use App\Services\EasypaisaStatusWorkerAllocator;
use App\Support\PayinCallbackTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class EasyPaisaCheckTransactionStatus extends Command
{
    protected $signature = 'transactions:easypaisa-check-status {--worker : Run as a status-check worker (do not spawn more workers)} {--token= : Worker claim token} {--worker-id=0 : Worker slot id for logs}';

    protected $description = 'Check status of pending transactions and update them.';

    private const COORDINATOR_LOCK_KEY = 'easypaisa-check-status:coordinator';

    private const WORKER_PIDS_CACHE_KEY = 'easypaisa-check-status:worker-pids';

    /**
     * Abandoned-claim lease (and worker-PID cache TTL).
     */
    private const COORDINATOR_LOCK_SECONDS = 300;

    /**
     * Coordinator FileLock TTL for the short scale-up critical section only.
     * FileLock cannot extend(); the lock is released after spawn, not held while workers run.
     */
    private const COORDINATOR_LOCK_TTL_SECONDS = 120;

    protected $statusService;

    protected $allocator;

    public function __construct(StatusService $statusService, EasypaisaStatusWorkerAllocator $allocator)
    {
        parent::__construct();
        $this->statusService = $statusService;
        $this->allocator = $allocator;
    }

    public function handle(): int
    {
        set_time_limit(0);

        if ($this->option('worker')) {
            return $this->runWorker();
        }

        return $this->runCoordinator();
    }

    private function runCoordinator(): int
    {
        $startedAt = microtime(true);
        $coordinatorPid = getmypid();
        $minAgeMinutes = $this->minAgeMinutes();

        $lock = $this->acquireCoordinatorLock($coordinatorPid);

        if ($lock === null) {
            return Command::SUCCESS;
        }

        try {
            $liveWorkers = $this->countLiveWorkers();
            $pendingCount = $this->countEligiblePending($minAgeMinutes);
            $desiredWorkers = $this->allocator->workerCount($pendingCount);
            $workersToSpawn = $this->allocator->workersToSpawn($desiredWorkers, $liveWorkers);

            Log::channel('schedule_debug')->info('EasyPaisa status scaling decision', [
                'coordinator_pid' => $coordinatorPid,
                'pending_count' => $pendingCount,
                'live_worker_count' => $liveWorkers,
                'desired_worker_count' => $desiredWorkers,
                'workers_to_spawn' => $workersToSpawn,
                'min_age_minutes' => $minAgeMinutes,
            ]);

            if ($this->allocator->shouldRecoverAbandonedClaims($liveWorkers)) {
                $recovered = $this->recoverAbandonedClaims();
                if ($recovered > 0) {
                    Log::channel('schedule_debug')->info('EasyPaisa abandoned IN_PROGRESS claims recovered', [
                        'coordinator_pid' => $coordinatorPid,
                        'recovered' => $recovered,
                        'lease_seconds' => self::COORDINATOR_LOCK_SECONDS,
                    ]);
                }
            }

            if ($workersToSpawn === 0) {
                $this->info('EasyPaisa status workers already at desired count.');

                return Command::SUCCESS;
            }

            $spawnedPids = $this->spawnWorkers($workersToSpawn, $coordinatorPid, $liveWorkers);
            $this->rememberWorkerPids($spawnedPids);

            if ($spawnedPids === []) {
                Log::channel('schedule_debug')->error('EasyPaisa status workers spawn produced no live workers', [
                    'coordinator_pid' => $coordinatorPid,
                    'pending_count' => $pendingCount,
                    'live_worker_count' => $liveWorkers,
                    'desired_worker_count' => $desiredWorkers,
                    'workers_to_spawn' => $workersToSpawn,
                ]);
                $this->error('EasyPaisa status workers failed to stay alive after spawn.');

                return Command::FAILURE;
            }

            Log::channel('schedule_debug')->info('EasyPaisa status workers spawned', [
                'coordinator_pid' => $coordinatorPid,
                'pending_count' => $pendingCount,
                'live_worker_count' => $liveWorkers,
                'desired_worker_count' => $desiredWorkers,
                'workers_to_spawn' => $workersToSpawn,
                'worker_pids' => $spawnedPids,
            ]);

            $this->info('EasyPaisa status workers scaled.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            Log::channel('schedule_debug')->error('EasyPaisa status coordinator failed', [
                'coordinator_pid' => $coordinatorPid,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);
            $this->error($exception->getMessage());

            return Command::FAILURE;
        } finally {
            try {
                $lock->release();
            } catch (Throwable $exception) {
                Log::channel('schedule_debug')->warning('EasyPaisa status coordinator lock release failed', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function runWorker(): int
    {
        $startedAt = microtime(true);
        $workerPid = getmypid();
        $workerId = (int) $this->option('worker-id');
        $token = trim((string) $this->option('token'));

        if ($token === '') {
            $token = bin2hex(random_bytes(16));
        }

        $liveWorkers = $this->countLiveWorkers();
        if ($liveWorkers > $this->allocator->maxWorkers()) {
            Log::channel('schedule_debug')->warning('EasyPaisa status worker refused — cap exceeded', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'live_workers' => $liveWorkers,
                'max_workers' => $this->allocator->maxWorkers(),
            ]);

            return Command::SUCCESS;
        }

        Log::channel('schedule_debug')->info('EasyPaisa status worker started', [
            'worker_pid' => $workerPid,
            'worker_id' => $workerId,
            'worker_token' => $token,
        ]);

        $minAgeMinutes = $this->minAgeMinutes();
        $processed = 0;
        $updatedSuccess = 0;
        $updatedFailed = 0;
        $released = 0;
        $errors = 0;
        $claimedId = null;

        try {
            while (true) {
                $item = $this->claimOneTransaction($token, $minAgeMinutes);

                if ($item === null) {
                    break;
                }

                $claimedId = $item->id;
                $processed++;

                $result = $this->processClaimedTransaction($item, $token, $workerPid, $workerId);

                $updatedSuccess += $result['success'] ? 1 : 0;
                $updatedFailed += $result['failed'] ? 1 : 0;
                $released += $result['released'] ? 1 : 0;
                $errors += $result['error'] ? 1 : 0;
                $claimedId = null;
            }
        } catch (Throwable $exception) {
            $errors++;
            Log::channel('schedule_debug')->error('EasyPaisa status worker failed', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'worker_token' => $token,
                'transaction_id' => $claimedId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if ($claimedId !== null) {
                $this->recoverAfterException($claimedId, $token);
            }

            return Command::FAILURE;
        } finally {
            if ($claimedId !== null) {
                $this->releaseToAvailable($claimedId, $token);
            }

            Log::channel('schedule_debug')->info('EasyPaisa status worker completed', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'worker_token' => $token,
                'processed' => $processed,
                'updated_success' => $updatedSuccess,
                'updated_failed' => $updatedFailed,
                'released_retried' => $released,
                'errors' => $errors,
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{success: bool, failed: bool, released: bool, error: bool}
     */
    private function processClaimedTransaction(Transaction $item, string $token, int $workerPid, int $workerId): array
    {
        $outcome = ['success' => false, 'failed' => false, 'released' => false, 'error' => false];

        try {
            $url = $item->url;
            $apiStarted = microtime(true);

            Log::channel('schedule_debug')->info('EasyPaisa status API request start', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'worker_token' => $token,
                'transaction_id' => $item->id,
                'claim_time' => optional($item->cron_claimed_at)->toDateTimeString(),
            ]);

            $result = $this->statusService->process($item);
            $apiSeconds = round(microtime(true) - $apiStarted, 2);

            Log::channel('schedule_debug')->info('EasyPaisa status API request end', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'transaction_id' => $item->id,
                'response_code' => $result['responseCode'] ?? null,
                'transaction_status' => $result['transactionStatus'] ?? null,
                'duration_seconds' => $apiSeconds,
            ]);

            if (($result['responseCode'] ?? '') === '0003') {
                $this->releaseToAvailable($item->id, $token);
                $outcome['released'] = true;

                return $outcome;
            }

            $item->refresh();
            if ($item->status !== 'pending') {
                $this->markDoneIfNotPending($item->id, $token);

                return $outcome;
            }

            if (($result['responseCode'] ?? '') === '0000') {
                if (($result['transactionStatus'] ?? '') === 'PAID') {
                    $updated = Transaction::query()
                        ->where('id', $item->id)
                        ->where('status', 'pending')
                        ->where('cron_claim_token', $token)
                        ->update([
                            'status' => 'success',
                            'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                            'cron_status' => Transaction::CRON_STATUS_DONE,
                            'cron_claim_token' => null,
                            'cron_claimed_at' => null,
                        ]);

                    if (!$updated) {
                        $this->markDoneIfNotPending($item->id, $token);

                        return $outcome;
                    }

                    $outcome['success'] = true;
                    $item->refresh();

                    $data = [
                        'orderId' => $item->orderId,
                        'tid' => $item->transactionId,
                        'amount' => $item->amount,
                        'status' => 'success',
                    ];

                    $user = User::find($item->user_id);

                    if ($user && $user->per_payin_fee) {
                        $rate = $user->per_payin_fee;
                        $amount = $item->amount * $rate;

                        $surplus = SurplusAmount::find(1);
                        $setting = Setting::where('user_id', $item->user_id)->first();
                        if ($setting && $surplus && $setting->auto == 1) {
                            $setting->payout_balance += $amount;
                            $setting->save();
                        }
                    }

                    $this->sendCronCallback('check-status', $item, $url, $data);
                } elseif (($result['transactionStatus'] ?? '') === 'FAILED') {
                    $updated = Transaction::query()
                        ->where('id', $item->id)
                        ->where('status', 'pending')
                        ->where('cron_claim_token', $token)
                        ->update([
                            'status' => 'failed',
                            'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                            'pp_code' => $result['errorCode'] ?? null,
                            'pp_message' => $result['errorReason'] ?? null,
                            'cron_status' => Transaction::CRON_STATUS_DONE,
                            'cron_claim_token' => null,
                            'cron_claimed_at' => null,
                        ]);

                    if (!$updated) {
                        $this->markDoneIfNotPending($item->id, $token);

                        return $outcome;
                    }

                    $outcome['failed'] = true;
                    $item->refresh();

                    $data = [
                        'orderId' => $item->orderId,
                        'tid' => $item->transactionId,
                        'amount' => $item->amount,
                        'status' => 'failed',
                    ];

                    $this->sendCronCallback('check-status', $item, $url, $data);
                } else {
                    $this->releaseToAvailable($item->id, $token);
                    $outcome['released'] = true;
                }
            } else {
                $this->releaseToAvailable($item->id, $token);
                $outcome['released'] = true;
            }
        } catch (Throwable $exception) {
            $outcome['error'] = true;
            Log::channel('schedule_debug')->error('EasyPaisa status worker item failed', [
                'worker_pid' => $workerPid,
                'worker_id' => $workerId,
                'worker_token' => $token,
                'transaction_id' => $item->id,
                'error' => $exception->getMessage(),
            ]);
            $this->recoverAfterException($item->id, $token);
        }

        return $outcome;
    }

    private function claimOneTransaction(string $token, int $minAgeMinutes): ?Transaction
    {
        $claimed = null;

        DB::transaction(function () use ($token, $minAgeMinutes, &$claimed) {
            $row = Transaction::query()
                ->where('status', 'pending')
                ->where('txn_type', 'easypaisa')
                ->where('created_at', '<=', now()->subMinutes($minAgeMinutes))
                ->where('cron_status', Transaction::CRON_STATUS_AVAILABLE)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->limit(1)
                ->first();

            if ($row === null) {
                return;
            }

            $claimedAt = now();
            $updated = Transaction::query()
                ->where('id', $row->id)
                ->where('cron_status', Transaction::CRON_STATUS_AVAILABLE)
                ->update([
                    'cron_status' => Transaction::CRON_STATUS_IN_PROGRESS,
                    'cron_claim_token' => $token,
                    'cron_claimed_at' => $claimedAt,
                ]);

            if (!$updated) {
                return;
            }

            $row->cron_status = Transaction::CRON_STATUS_IN_PROGRESS;
            $row->cron_claim_token = $token;
            $row->cron_claimed_at = $claimedAt;
            $claimed = $row;
        });

        if ($claimed !== null) {
            Log::channel('schedule_debug')->info('EasyPaisa status worker claimed transaction', [
                'worker_pid' => getmypid(),
                'worker_token' => $token,
                'transaction_id' => $claimed->id,
                'claim_time' => optional($claimed->cron_claimed_at)->toDateTimeString(),
            ]);
        }

        return $claimed;
    }

    private function releaseToAvailable(int $transactionId, string $token): void
    {
        $updated = Transaction::query()
            ->where('id', $transactionId)
            ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
            ->where('cron_claim_token', $token)
            ->update([
                'cron_status' => Transaction::CRON_STATUS_AVAILABLE,
                'cron_claim_token' => null,
                'cron_claimed_at' => null,
            ]);

        if ($updated) {
            Log::channel('schedule_debug')->info('EasyPaisa status claim released to available', [
                'worker_pid' => getmypid(),
                'worker_token' => $token,
                'transaction_id' => $transactionId,
            ]);
        }
    }

    private function markDoneIfNotPending(int $transactionId, string $token): void
    {
        Transaction::query()
            ->where('id', $transactionId)
            ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
            ->where('cron_claim_token', $token)
            ->where('status', '!=', 'pending')
            ->update([
                'cron_status' => Transaction::CRON_STATUS_DONE,
                'cron_claim_token' => null,
                'cron_claimed_at' => null,
            ]);
    }

    private function recoverAfterException(int $transactionId, string $token): void
    {
        $fresh = Transaction::query()->find($transactionId);

        if (!$fresh) {
            return;
        }

        if ($fresh->status !== 'pending') {
            $this->markDoneIfNotPending($transactionId, $token);

            return;
        }

        $this->releaseToAvailable($transactionId, $token);
    }

    private function recoverAbandonedClaims(): int
    {
        return Transaction::query()
            ->where('status', 'pending')
            ->where('txn_type', 'easypaisa')
            ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
            ->where(function ($query) {
                $query->whereNull('cron_claimed_at')
                    ->orWhere('cron_claimed_at', '<=', now()->subSeconds(self::COORDINATOR_LOCK_SECONDS));
            })
            ->update([
                'cron_status' => Transaction::CRON_STATUS_AVAILABLE,
                'cron_claim_token' => null,
                'cron_claimed_at' => null,
            ]);
    }

    private function countEligiblePending(int $minAgeMinutes): int
    {
        return Transaction::query()
            ->where('status', 'pending')
            ->where('txn_type', 'easypaisa')
            ->where('created_at', '<=', now()->subMinutes($minAgeMinutes))
            ->count();
    }

    private function minAgeMinutes(): int
    {
        return (int) config('payin_status_cron.min_age_minutes', 2);
    }

    /**
     * Start additional detached workers and return only verified Laravel worker PIDs.
     *
     * @return list<int>
     */
    private function spawnWorkers(int $workerCount, int $coordinatorPid, int $liveWorkers = 0): array
    {
        $workerCount = min($this->allocator->maxWorkers(), max(0, $workerCount));
        $spawnedPids = [];

        for ($slot = 1; $slot <= $workerCount; $slot++) {
            $token = bin2hex(random_bytes(16));
            $workerId = $liveWorkers + $slot;
            $pid = $this->spawnDetachedWorker($token, $workerId);

            if ($pid === null) {
                continue;
            }

            $spawnedPids[] = $pid;

            Log::channel('schedule_debug')->info('EasyPaisa status worker process started', [
                'coordinator_pid' => $coordinatorPid,
                'worker_id' => $workerId,
                'worker_pid' => $pid,
                'worker_token' => $token,
            ]);
        }

        return $spawnedPids;
    }

    /**
     * Detach a worker from the coordinator process tree.
     * Symfony Process v6.4.20 __destruct() calls stop(0) → SIGTERM/SIGKILL, so it cannot be used here.
     */
    private function spawnDetachedWorker(string $token, int $workerId): ?int
    {
        $command = sprintf(
            'setsid exec %s %s transactions:easypaisa-check-status --worker --token=%s --worker-id=%d < /dev/null > /dev/null 2>&1 & echo $!',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(base_path('artisan')),
            escapeshellarg($token),
            $workerId
        );

        $pipes = [];
        $proc = @proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, base_path());

        if (!is_resource($proc)) {
            Log::channel('schedule_debug')->error('EasyPaisa status worker spawn failed — proc_open returned false', [
                'worker_id' => $workerId,
            ]);

            return null;
        }

        fclose($pipes[0]);
        $stdout = trim((string) stream_get_contents($pipes[1]));
        $stderr = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        $pid = (int) $stdout;

        if ($pid <= 1) {
            Log::channel('schedule_debug')->error('EasyPaisa status worker spawn failed — no worker PID', [
                'worker_id' => $workerId,
                'stdout' => $stdout,
                'stderr' => $stderr,
            ]);

            return null;
        }

        if (!$this->waitForWorkerPid($pid)) {
            Log::channel('schedule_debug')->error('EasyPaisa status worker spawn failed — PID is not a live --worker process', [
                'worker_id' => $workerId,
                'reported_pid' => $pid,
                'stderr' => $stderr,
            ]);

            return null;
        }

        return $pid;
    }

    private function waitForWorkerPid(int $pid): bool
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            if ($this->isEasyPaisaWorkerPid($pid)) {
                return true;
            }

            usleep(50000);
        }

        return $this->isEasyPaisaWorkerPid($pid);
    }

    private function isEasyPaisaWorkerPid(int $pid): bool
    {
        if ($pid <= 1) {
            return false;
        }

        $cmdlinePath = '/proc/' . $pid . '/cmdline';
        if (!is_readable($cmdlinePath)) {
            return false;
        }

        $cmdline = @file_get_contents($cmdlinePath);
        if ($cmdline === false || $cmdline === '') {
            return false;
        }

        $cmdline = str_replace("\0", ' ', $cmdline);

        return str_contains($cmdline, 'transactions:easypaisa-check-status')
            && str_contains($cmdline, '--worker');
    }

    /**
     * @param  list<int|null>  $spawnedPids
     */
    private function rememberWorkerPids(array $spawnedPids): void
    {
        $existing = [];
        foreach ((array) Cache::get(self::WORKER_PIDS_CACHE_KEY, []) as $pid) {
            $pid = (int) $pid;
            if ($pid > 0 && ProcessHelper::isProcessAlive($pid) !== false) {
                $existing[] = $pid;
            }
        }

        $new = [];
        foreach ($spawnedPids as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $new[] = $pid;
            }
        }

        Cache::put(
            self::WORKER_PIDS_CACHE_KEY,
            array_values(array_unique(array_merge($existing, $new))),
            self::COORDINATOR_LOCK_SECONDS
        );
    }

    /**
     * Acquire the short-lived coordinator lock.
     *
     * schedule:run wraps this command in `sh -c`, so pgrep often sees 1 extra
     * non-PHP line. That must not block recovery. A lock older than the 120s TTL
     * cannot belong to a live scaling critical section (including the leftover 12h lock).
     *
     * @return \Illuminate\Contracts\Cache\Lock|null
     */
    private function acquireCoordinatorLock(int $coordinatorPid)
    {
        $lock = Cache::lock(self::COORDINATOR_LOCK_KEY, self::COORDINATOR_LOCK_TTL_SECONDS);

        if ($lock->get()) {
            return $lock;
        }

        $otherCoordinators = $this->countOtherCoordinatorProcesses($coordinatorPid);
        $lockAgeSeconds = $this->coordinatorLockAgeSeconds();
        $lockLooksFresh = $lockAgeSeconds !== null
            && $lockAgeSeconds < self::COORDINATOR_LOCK_TTL_SECONDS;

        if ($lockLooksFresh) {
            Log::channel('schedule_debug')->warning('EasyPaisa status coordinator skipped — another coordinator holds the lock', [
                'coordinator_pid' => $coordinatorPid,
                'other_coordinator_count' => $otherCoordinators,
                'lock_age_seconds' => $lockAgeSeconds,
                'lock_ttl_seconds' => self::COORDINATOR_LOCK_TTL_SECONDS,
            ]);
            $this->warn('Another EasyPaisa status coordinator is already running.');

            return null;
        }

        if ($lockAgeSeconds === null && $otherCoordinators !== 0) {
            Log::channel('schedule_debug')->warning('EasyPaisa status coordinator skipped — another coordinator holds the lock', [
                'coordinator_pid' => $coordinatorPid,
                'other_coordinator_count' => $otherCoordinators,
                'lock_age_seconds' => $lockAgeSeconds,
                'lock_ttl_seconds' => self::COORDINATOR_LOCK_TTL_SECONDS,
            ]);
            $this->warn('Another EasyPaisa status coordinator is already running.');

            return null;
        }

        Log::channel('schedule_debug')->warning('EasyPaisa status stale coordinator lock recovered', [
            'coordinator_pid' => $coordinatorPid,
            'other_coordinator_count' => $otherCoordinators,
            'lock_age_seconds' => $lockAgeSeconds,
            'lock_ttl_seconds' => self::COORDINATOR_LOCK_TTL_SECONDS,
        ]);

        $lock->forceRelease();

        if (!$lock->get()) {
            Log::channel('schedule_debug')->warning('EasyPaisa status coordinator skipped — lock reacquired by another process after stale release', [
                'coordinator_pid' => $coordinatorPid,
            ]);
            $this->warn('Another EasyPaisa status coordinator is already running.');

            return null;
        }

        return $lock;
    }

    private function countOtherCoordinatorProcesses(int $selfPid): ?int
    {
        try {
            $process = new Process(['pgrep', '-af', 'transactions:easypaisa-check-status']);
            $process->setTimeout(3);
            $process->run();
            $exitCode = $process->getExitCode();

            if ($exitCode === 1) {
                return 0;
            }

            if ($exitCode !== 0) {
                return null;
            }

            $count = 0;
            $lines = preg_split('/\r\n|\r|\n/', trim($process->getOutput())) ?: [];

            foreach ($lines as $line) {
                if (!$this->isPhpArtisanCoordinatorLine($line, $selfPid)) {
                    continue;
                }

                $count++;
            }

            return $count;
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function isPhpArtisanCoordinatorLine(string $line, int $selfPid): bool
    {
        if ($line === '' || str_contains($line, 'pgrep') || str_contains($line, '--worker')) {
            return false;
        }

        $pid = (int) explode(' ', ltrim($line), 2)[0];
        if ($pid <= 0 || $pid === $selfPid) {
            return false;
        }

        if (preg_match('/\b(?:sh|bash|dash|zsh)\b.*\s-c\b/', $line)) {
            return false;
        }

        return (bool) preg_match('/\bphp(?:\d+(?:\.\d+)?)?\b/i', $line);
    }

    private function coordinatorLockAgeSeconds(): ?int
    {
        try {
            $store = Cache::getStore();
            if (!method_exists($store, 'path')) {
                return null;
            }

            $path = $store->path(Cache::getPrefix() . self::COORDINATOR_LOCK_KEY);
            if (!is_string($path) || $path === '' || !is_file($path)) {
                return null;
            }

            $mtime = filemtime($path);
            if ($mtime === false) {
                return null;
            }

            return max(0, time() - $mtime);
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function countLiveWorkers(): int
    {
        $pgrepCount = $this->pgrepWorkerCount();
        if ($pgrepCount !== null) {
            return $pgrepCount;
        }

        $cachedPids = Cache::get(self::WORKER_PIDS_CACHE_KEY, []);
        $live = 0;

        foreach ((array) $cachedPids as $pid) {
            $alive = ProcessHelper::isProcessAlive((int) $pid);
            if ($alive !== false) {
                $live++;
            }
        }

        return $live;
    }

    private function pgrepWorkerCount(): ?int
    {
        try {
            $process = new Process(['pgrep', '-fc', 'transactions:easypaisa-check-status --worker']);
            $process->setTimeout(3);
            $process->run();
            $output = trim($process->getOutput());

            if ($output !== '' && is_numeric($output)) {
                return max(0, (int) $output);
            }

            if ($process->getExitCode() === 1) {
                return 0;
            }
        } catch (Throwable $exception) {
            // pgrep is unavailable (Windows); fall back to cached PIDs.
        }

        return null;
    }

    private function sendCronCallback(string $cron, Transaction $item, ?string $url, array $data): void
    {
        $logger = Log::channel('payin');
        $context = 'easypaisa_cron_' . $cron;

        $logger->info('Easypaisa cron callback sending', [
            'context' => $context,
            'order_id' => $item->orderId,
            'transaction_id' => $item->id,
            'callback_url' => $url,
            'callback_data' => $data,
        ]);

        if ($url === null || trim((string) $url) === '') {
            PayinCallbackTracker::recordSkipped($item, 'empty callback url');

            return;
        }

        PayinCallbackTracker::markSending($item);

        try {
            $response = Http::timeout(60)->post($url, $data);

            $logger->info('Easypaisa cron callback response received', [
                'context' => $context,
                'order_id' => $item->orderId,
                'transaction_id' => $item->id,
                'callback_url' => $url,
                'callback_data' => $data,
                'response_status' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
            ]);

            PayinCallbackTracker::record($item, (string) ($data['status'] ?? ''), $response);
        } catch (Throwable $e) {
            $logger->error('Easypaisa cron callback failed', [
                'context' => $context,
                'order_id' => $item->orderId,
                'transaction_id' => $item->id,
                'callback_url' => $url,
                'callback_data' => $data,
                'error' => $e->getMessage(),
            ]);

            PayinCallbackTracker::record($item, (string) ($data['status'] ?? ''), null, $e);
        }
    }
}
