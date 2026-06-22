<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Service\StatusService;
use App\Services\EasypaisaCronChunkService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasyPaisaTransactionRecheckStatus extends Command
{
    protected $signature = 'transactions:easypaisa-recheck-status';

    protected $description = 'Recheck Easypaisa transactions falsely marked failed (0003 INVALID ORDER ID).';

    protected $statusService;

    protected $chunkService;

    public function __construct(StatusService $statusService, EasypaisaCronChunkService $chunkService)
    {
        parent::__construct();
        $this->statusService = $statusService;
        $this->chunkService = $chunkService;
    }

    public function handle()
    {
        $chunk = $this->chunkService->getChunk('recheck');

        $list = Transaction::where('status', 'failed')
            ->where('pp_code', '0003')
            ->where('txn_type', 'easypaisa')
            ->where('created_at', '<=', now()->subMinutes(5))
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
                if ($item->status === 'success') {
                    continue;
                }

                if (($result['responseCode'] ?? '') === '0000' && ($result['transactionStatus'] ?? '') === 'PAID') {
                    $updated = Transaction::where('id', $item->id)
                        ->where('status', 'failed')
                        ->update([
                            'status' => 'success',
                            'transactionId' => $result['transactionId'] ?? $result['msisdn'] ?? null,
                            'pp_code' => $result['responseCode'],
                            'pp_message' => $result['responseDesc'] ?? null,
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
                    $this->sendCronCallback('recheck-status', $item, $url, $data);
                } elseif (($result['responseCode'] ?? '') === '0000' && ($result['transactionStatus'] ?? '') === 'FAILED') {
                    $updated = Transaction::where('id', $item->id)
                        ->where('status', 'failed')
                        ->update([
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
                    $this->sendCronCallback('recheck-status', $item, $url, $data);
                }
            }
        }

        $this->chunkService->logRunContext('recheck-status', $chunk, $processed);

        $this->info('Failed Easypaisa transactions rechecked and updated.');
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
