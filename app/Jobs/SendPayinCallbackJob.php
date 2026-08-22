<?php

namespace App\Jobs;

use App\Models\ArcheiveTransaction;
use App\Models\BackupTransaction;
use App\Models\Transaction;
use App\Support\PayinCallbackTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPayinCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CALLBACK_HTTP_TIMEOUT_SECONDS = 20;

    public int $tries = 3;
    public int $timeout = 20;

    public ?int $transactionId = null;

    public function __construct(
        public string $callbackUrl,
        public array $payload,
        public string $requestId,
        public string $context = 'payin_callback',
        ?int $transactionId = null
    ) {
        $this->transactionId = $transactionId;
    }

    public function handle(): void
    {
        $logger = Log::channel('payin');
        $startedAt = microtime(true);
        $callbackStatus = strtolower((string) ($this->payload['status'] ?? ''));
        $transaction = $this->resolveTransaction();

        PayinCallbackTracker::markSending($transaction);

        try {
            $response = Http::timeout(self::CALLBACK_HTTP_TIMEOUT_SECONDS)
                ->post($this->callbackUrl, $this->payload);

            $logger->info('Queued callback response received', [
                'request_id' => $this->requestId,
                'context' => $this->context,
                'callback_url' => $this->callbackUrl,
                'callback_data' => $this->payload,
                'response_status' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
                'queue_execution_time' => microtime(true) - $startedAt,
            ]);

            PayinCallbackTracker::record($transaction, $callbackStatus, $response);
        } catch (Throwable $e) {
            $logger->error('Queued callback failed', [
                'request_id' => $this->requestId,
                'context' => $this->context,
                'callback_url' => $this->callbackUrl,
                'callback_data' => $this->payload,
                'error' => $e->getMessage(),
                'queue_execution_time' => microtime(true) - $startedAt,
            ]);

            PayinCallbackTracker::record($transaction, $callbackStatus, null, $e);

            throw $e;
        }
    }

    private function resolveTransaction(): ?Model
    {
        if (! $this->transactionId) {
            return null;
        }

        return Transaction::query()->find($this->transactionId)
            ?? ArcheiveTransaction::query()->find($this->transactionId)
            ?? BackupTransaction::query()->find($this->transactionId);
    }
}
