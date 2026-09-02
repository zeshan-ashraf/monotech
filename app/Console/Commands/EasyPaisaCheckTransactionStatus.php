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
     * Coordinator FileLock TTL for the short scale-up critical section.
     * FileLock cannot extend(); the lock is released after spawn, not held while workers run.
     */
    private const COORDINATOR_LOCK_TTL_SECONDS = 43200;

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

        $lock = Cache::lock(self::COORDINATOR_LOCK_KEY, self::COORDINATOR_LOCK_TTL_SECONDS);

        if (!$lock->get()) {
            Log::channel('schedule_debug')->warning('EasyPaisa status coordinator skipped — another coordinator holds the lock', [
                'coordinator_pid' => $coordinatorPid,
            ]);
            $this->warn('Another EasyPaisa status coordinator is already running.');

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

            $processes = $this->spawnWorkers($workersToSpawn, $coordinatorPid, $liveWorkers);
            $spawnedPids = [];
            foreach ($processes as $index => $process) {
                $spawnedPids[$index] = $process->getPid();
            }

            $this->rememberWorkerPids($spawnedPids);

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
     * @param  int  $workerCount  Additional workers to start (not the full desired pool).
     * @return list<Process>
     */
    private function spawnWorkers(int $workerCount, int $coordinatorPid, int $liveWorkers = 0): array
    {
        $workerCount = min($this->allocator->maxWorkers(), max(0, $workerCount));
        $processes = [];

        for ($slot = 1; $slot <= $workerCount; $slot++) {
            $token = bin2hex(random_bytes(16));
            $workerId = $liveWorkers + $slot;
            $process = new Process([
                PHP_BINARY,
                base_path('artisan'),
                'transactions:easypaisa-check-status',
                '--worker',
                '--token=' . $token,
                '--worker-id=' . $workerId,
            ]);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(null);
            $process->disableOutput();
            $process->start();
            $processes[] = $process;

            Log::channel('schedule_debug')->info('EasyPaisa status worker process started', [
                'coordinator_pid' => $coordinatorPid,
                'worker_id' => $workerId,
                'worker_pid' => $process->getPid(),
                'worker_token' => $token,
            ]);
        }

        return $processes;
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
            self::COORDINATOR_LOCK_TTL_SECONDS
        );
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
