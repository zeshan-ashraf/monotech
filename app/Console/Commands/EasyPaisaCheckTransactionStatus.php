<?php

namespace App\Console\Commands;

use App\Models\{Setting, SurplusAmount, Transaction, User};
use App\Service\StatusService;
use App\Services\EasypaisaCronChunkService;
use App\Support\PayinCallbackTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EasyPaisaCheckTransactionStatus extends Command
{
    protected $signature = 'transactions:easypaisa-check-status';

    protected $description = 'Check status of pending transactions and update them.';

    /**
     * Previous global Cache::lock TTL. Used to reclaim crashed IN_PROGRESS rows.
     */
    private const STALE_IN_PROGRESS_SECONDS = 300;

    protected $statusService;

    protected $chunkService;

    public function __construct(StatusService $statusService, EasypaisaCronChunkService $chunkService)
    {
        parent::__construct();
        $this->statusService = $statusService;
        $this->chunkService = $chunkService;
    }

    public function handle(): int
    {
        $startedAt = microtime(true);
        $chunk = $this->chunkService->getChunk('check');
        $workerPid = getmypid();
        $claimed = collect();
        $claimedAtById = [];

        Log::channel('schedule_debug')->info('transactions:easypaisa-check-status started', [
            'worker_pid' => $workerPid,
            'chunk_limit' => $chunk,
            'schedule_type' => $this->chunkService->getActiveScheduleType(),
        ]);

        try {
            set_time_limit(0);

            $minAgeMinutes = (int) config('payin_status_cron.min_age_minutes', 2);
            $claimed = $this->claimPendingTransactions($chunk, $minAgeMinutes);
            $claimedAtById = $claimed->mapWithKeys(function (Transaction $item) {
                return [$item->id => $item->updated_at];
            })->all();

            Log::channel('schedule_debug')->info('transactions:easypaisa-check-status claimed', [
                'worker_pid' => $workerPid,
                'claimed_count' => $claimed->count(),
                'claimed_ids' => $claimed->pluck('id')->all(),
            ]);

            $processed = 0;
            $updatedSuccess = 0;
            $updatedFailed = 0;

            foreach ($claimed as $item) {
                $claimedAt = $claimedAtById[$item->id] ?? $item->updated_at;

                try {
                    $processed++;
                    $url = $item->url;

                    $result = $this->statusService->process($item);

                    if (($result['responseCode'] ?? '') === '0003') {
                        $this->releaseToAvailable($item->id, $claimedAt);
                        continue;
                    }

                    $item->refresh();
                    if ($item->status !== 'pending') {
                        $this->markDoneIfNotPending($item->id);
                        continue;
                    }

                    if (($result['responseCode'] ?? '') === '0000') {
                        if (($result['transactionStatus'] ?? '') === 'PAID') {
                            $updated = Transaction::query()
                                ->where('id', $item->id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'success',
                                    'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                                    'cron_status' => Transaction::CRON_STATUS_DONE,
                                ]);

                            if (!$updated) {
                                $this->markDoneIfNotPending($item->id);
                                continue;
                            }

                            $updatedSuccess++;
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
                                ->update([
                                    'status' => 'failed',
                                    'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                                    'pp_code' => $result['errorCode'] ?? null,
                                    'pp_message' => $result['errorReason'] ?? null,
                                    'cron_status' => Transaction::CRON_STATUS_DONE,
                                ]);

                            if (!$updated) {
                                $this->markDoneIfNotPending($item->id);
                                continue;
                            }

                            $updatedFailed++;
                            $item->refresh();

                            $data = [
                                'orderId' => $item->orderId,
                                'tid' => $item->transactionId,
                                'amount' => $item->amount,
                                'status' => 'failed',
                            ];

                            $this->sendCronCallback('check-status', $item, $url, $data);
                        } else {
                            $this->releaseToAvailable($item->id, $claimedAt);
                        }
                    } else {
                        $this->releaseToAvailable($item->id, $claimedAt);
                    }
                } catch (Throwable $exception) {
                    Log::channel('schedule_debug')->error('transactions:easypaisa-check-status item failed', [
                        'worker_pid' => $workerPid,
                        'transaction_id' => $item->id,
                        'error' => $exception->getMessage(),
                    ]);

                    $this->recoverAfterException($item, $claimedAt);
                }
            }

            $this->chunkService->logRunContext('check-status', $chunk, $processed);

            Log::channel('schedule_debug')->info('transactions:easypaisa-check-status completed', [
                'worker_pid' => $workerPid,
                'chunk_limit' => $chunk,
                'claimed_count' => $claimed->count(),
                'processed' => $processed,
                'updated_success' => $updatedSuccess,
                'updated_failed' => $updatedFailed,
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            $this->info('Pending transactions checked and updated.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            Log::channel('schedule_debug')->error('transactions:easypaisa-check-status failed', [
                'worker_pid' => $workerPid,
                'chunk_limit' => $chunk,
                'claimed_count' => $claimed->count(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            $this->error($exception->getMessage());

            return Command::FAILURE;
        } finally {
            $this->releaseRemainingClaims($claimedAtById);
        }
    }

    /**
     * Atomically claim a unique batch. Row locks are released on commit, before EasyPaisa HTTP calls.
     *
     * @return Collection<int, Transaction>
     */
    private function claimPendingTransactions(int $chunk, int $minAgeMinutes): Collection
    {
        $claimed = collect();

        DB::transaction(function () use ($chunk, $minAgeMinutes, &$claimed) {
            $eligible = Transaction::query()
                ->where('status', 'pending')
                ->where('txn_type', 'easypaisa')
                ->where('created_at', '<=', now()->subMinutes($minAgeMinutes));

            $rows = (clone $eligible)
                ->where('cron_status', Transaction::CRON_STATUS_AVAILABLE)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->limit($chunk)
                ->get();

            $remaining = $chunk - $rows->count();

            if ($remaining > 0) {
                $stale = (clone $eligible)
                    ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
                    ->where('updated_at', '<=', now()->subSeconds(self::STALE_IN_PROGRESS_SECONDS))
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->limit($remaining)
                    ->get();

                $rows = $rows->concat($stale)->unique('id')->values();
            }

            if ($rows->isEmpty()) {
                $claimed = $rows;

                return;
            }

            $claimedAt = now();

            Transaction::query()
                ->whereIn('id', $rows->pluck('id'))
                ->update([
                    'cron_status' => Transaction::CRON_STATUS_IN_PROGRESS,
                    'updated_at' => $claimedAt,
                ]);

            $rows->each(function (Transaction $row) use ($claimedAt) {
                $row->cron_status = Transaction::CRON_STATUS_IN_PROGRESS;
                $row->updated_at = $claimedAt;
            });

            $claimed = $rows;
        });

        return $claimed;
    }

    private function releaseToAvailable(int $transactionId, mixed $claimedAt): void
    {
        Transaction::query()
            ->where('id', $transactionId)
            ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
            ->where('updated_at', $claimedAt)
            ->update(['cron_status' => Transaction::CRON_STATUS_AVAILABLE]);
    }

    private function markDoneIfNotPending(int $transactionId): void
    {
        Transaction::query()
            ->where('id', $transactionId)
            ->where('cron_status', Transaction::CRON_STATUS_IN_PROGRESS)
            ->where('status', '!=', 'pending')
            ->update(['cron_status' => Transaction::CRON_STATUS_DONE]);
    }

    private function recoverAfterException(Transaction $item, mixed $claimedAt): void
    {
        $fresh = Transaction::query()->find($item->id);

        if (!$fresh) {
            return;
        }

        if ($fresh->status !== 'pending') {
            $this->markDoneIfNotPending($item->id);

            return;
        }

        $this->releaseToAvailable($item->id, $claimedAt);
    }

    /**
     * Release leftover IN_PROGRESS rows from this worker using the original claim timestamp
     * so a later worker's claim (different updated_at) cannot be overwritten.
     *
     * @param  array<int, mixed>  $claimedAtById
     */
    private function releaseRemainingClaims(array $claimedAtById): void
    {
        foreach ($claimedAtById as $transactionId => $claimedAt) {
            $this->releaseToAvailable((int) $transactionId, $claimedAt);
        }
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
