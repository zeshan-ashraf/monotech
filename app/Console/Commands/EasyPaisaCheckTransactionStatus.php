<?php

namespace App\Console\Commands;

use App\Models\{Setting, SurplusAmount, Transaction, User};
use App\Service\StatusService;
use App\Services\EasypaisaCronChunkService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EasyPaisaCheckTransactionStatus extends Command
{
    protected $signature = 'transactions:easypaisa-check-status';

    protected $description = 'Check status of pending transactions and update them.';

    private const LOCK_KEY = 'easypaisa-check-status-lock';

    private const LOCK_SECONDS = 600;

    private const TRANSACTION_LOCK_SECONDS = 120;

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
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

        if (!$lock->get()) {
            Log::channel('schedule_debug')->warning('transactions:easypaisa-check-status skipped — another instance holds the lock');

            $this->warn('Another instance of this command is already running.');

            return Command::SUCCESS;
        }

        $startedAt = microtime(true);
        $chunk = $this->chunkService->getChunk('check');

        Log::channel('schedule_debug')->info('transactions:easypaisa-check-status started', [
            'chunk_limit' => $chunk,
            'schedule_type' => $this->chunkService->getActiveScheduleType(),
        ]);

        try {
            set_time_limit(0);

            $list = Transaction::query()
                ->where('status', 'pending')
                ->where('txn_type', 'easypaisa')
                ->orderBy('created_at', 'asc')
                ->limit($chunk)
                ->get();

            $processed = 0;
            $skippedLocked = 0;
            $updatedSuccess = 0;
            $updatedFailed = 0;

            if ($list->isNotEmpty()) {
                foreach ($list as $item) {
                    $transactionLock = Cache::lock(
                        'easypaisa-check-status:txn:' . $item->id,
                        self::TRANSACTION_LOCK_SECONDS
                    );

                    if (!$transactionLock->get()) {
                        $skippedLocked++;
                        continue;
                    }

                    try {
                        $processed++;
                        $url = $item->url;

                        $result = $this->statusService->process($item);

                        if (($result['responseCode'] ?? '') === '0003') {
                            continue;
                        }

                        $item->refresh();
                        if ($item->status !== 'pending') {
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
                                    ]);

                                if (!$updated) {
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
                                    ]);

                                if (!$updated) {
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
                            }
                        }
                    } finally {
                        $this->releaseLock($transactionLock);
                    }
                }
            }

            $this->chunkService->logRunContext('check-status', $chunk, $processed);

            Log::channel('schedule_debug')->info('transactions:easypaisa-check-status completed', [
                'chunk_limit' => $chunk,
                'processed' => $processed,
                'skipped_transaction_lock' => $skippedLocked,
                'updated_success' => $updatedSuccess,
                'updated_failed' => $updatedFailed,
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            $this->info('Pending transactions checked and updated.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            Log::channel('schedule_debug')->error('transactions:easypaisa-check-status failed', [
                'chunk_limit' => $chunk,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);

            $this->error($exception->getMessage());

            return Command::FAILURE;
        } finally {
            $this->releaseLock($lock);
        }
    }

    private function releaseLock(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (Throwable $exception) {
            Log::channel('schedule_debug')->warning('transactions:easypaisa-check-status lock release failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendCronCallback(string $cron, Transaction $item, string $url, array $data): void
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
        } catch (Throwable $e) {
            $logger->error('Easypaisa cron callback failed', [
                'context' => $context,
                'order_id' => $item->orderId,
                'transaction_id' => $item->id,
                'callback_url' => $url,
                'callback_data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
