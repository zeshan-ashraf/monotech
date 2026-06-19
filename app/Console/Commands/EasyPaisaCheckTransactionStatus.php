<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Transaction,SurplusAmount,Setting,User};
use App\Service\StatusService;
use App\Services\EasypaisaCronChunkService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EasyPaisaCheckTransactionStatus extends Command
{
    // The name and signature of the console command.
    protected $signature = 'transactions:easypaisa-check-status';

    // The console command description.
    protected $description = 'Check status of pending transactions and update them.';

    // Dependency injection for the StatusService
    protected $statusService;

    protected $chunkService;

    public function __construct(StatusService $statusService, EasypaisaCronChunkService $chunkService)
    {
        parent::__construct();
        $this->statusService = $statusService;
        $this->chunkService = $chunkService;
    }

    // Execute the console command.
    public function handle()
    {
        // Acquire global lock to prevent multiple instances
        $lock = Cache::lock('easypaisa-check-status-lock', 300); // 5 minutes timeout
        
        if (!$lock->get()) {
            Log::info('EasyPaisaCheckTransactionStatus: Another instance is already running');
            $this->error('Another instance of this command is already running.');
            return 1;
        }

        try {
            $chunk = $this->chunkService->getChunk('check');

            $list = Transaction::where('status', 'pending')
                ->where('txn_type', 'easypaisa')
                ->orderBy('created_at', 'asc')
                ->limit($chunk)
                ->get();

            set_time_limit(0);
            $processed = 0;

            if ($list->isNotEmpty()) {
                foreach ($list as $item) {
                    $processed++;
                    $url = $item->url;

                    $result = $this->statusService->process($item);

                    // Guard 1 — Skip 0003 (treat as in-progress, not failed)
                    if (($result['responseCode'] ?? '') === '0003') {
                        continue;
                    }

                    // Guard 2 — Skip if checkout or another worker already finalized
                    $item->refresh();
                    if ($item->status !== 'pending') {
                        continue;
                    }

                    if (($result['responseCode'] ?? '') === '0000') {
                        if (($result['transactionStatus'] ?? '') === 'PAID') {
                            $updated = Transaction::where('id', $item->id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'success',
                                    'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                                ]);

                            // Guard 3 — Callback only after safe DB update
                            if (!$updated) {
                                continue;
                            }

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
                            $updated = Transaction::where('id', $item->id)
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
                }
            }

            $this->chunkService->logRunContext('check-status', $chunk, $processed);
        } finally {
            // Always release the global lock
            $lock->release();
        }

        $this->info('Pending transactions checked and updated.');
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
        } catch (\Throwable $e) {
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
