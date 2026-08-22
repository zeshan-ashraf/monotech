<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Records whether a final (success/failed) payin merchant callback was sent.
 * Copy this file with the callback_sent migrations to other projects.
 */
class PayinCallbackTracker
{
    public const RESPONSE_MAX = 500;

    /** @var list<string> */
    public const FINAL_STATUSES = ['success', 'failed'];

    /**
     * Stamp the moment we fire the HTTP POST to the client.
     */
    public static function markSending(?Model $transaction): void
    {
        if (! $transaction || ! $transaction->getKey()) {
            return;
        }

        try {
            $transaction->newQuery()
                ->whereKey($transaction->getKey())
                ->update([
                    'callback_sent_at' => now(),
                ]);
        } catch (Throwable) {
            // Never break payment / cron flow.
        }
    }

    public static function record(
        ?Model $transaction,
        string $callbackStatus,
        ?Response $response = null,
        ?Throwable $error = null
    ): void {
        if (! $transaction || ! $transaction->getKey()) {
            return;
        }

        try {
            $payload = [
                'callback_response' => self::shortReply($response, $error),
            ];

            if ($response !== null) {
                $payload['callback_response_at'] = now();
            }

            $isFinal = in_array(strtolower($callbackStatus), self::FINAL_STATUSES, true);
            $httpOk = $error === null && $response !== null && $response->successful();

            if ($isFinal && $httpOk) {
                $payload['callback_sent'] = 1;
            }

            $transaction->newQuery()
                ->whereKey($transaction->getKey())
                ->update($payload);
        } catch (Throwable) {
            // Never break payment / cron flow.
        }
    }

    public static function recordSkipped(?Model $transaction, string $reason): void
    {
        if (! $transaction || ! $transaction->getKey()) {
            return;
        }

        try {
            $transaction->newQuery()
                ->whereKey($transaction->getKey())
                ->update([
                    'callback_response' => self::truncate('skipped | ' . $reason),
                ]);
        } catch (Throwable) {
            // Never break payment / cron flow.
        }
    }

    /**
     * POST the merchant callback and store the short reply.
     * Sets callback_sent only for success/failed + HTTP 2xx.
     */
    public static function sendAndRecord(
        ?Model $transaction,
        ?string $url,
        array $payload,
        int $timeout = 60
    ): bool {
        $status = strtolower((string) ($payload['status'] ?? ''));

        if (! $transaction) {
            return false;
        }

        if ($url === null || trim($url) === '') {
            self::recordSkipped($transaction, 'empty callback url');

            return false;
        }

        self::markSending($transaction);

        try {
            $response = Http::timeout($timeout)->post($url, $payload);
            self::record($transaction, $status, $response);

            return $response->successful();
        } catch (Throwable $e) {
            self::record($transaction, $status, null, $e);

            return false;
        }
    }

    /**
     * @return array{callbackSent: mixed, callbackResponse: string, callbackSentAt: mixed, callbackResponseAt: mixed}
     */
    public static function badgeData(object $query): array
    {
        return [
            'callbackSent' => $query->callback_sent ?? 0,
            'callbackResponse' => $query->callback_response ?? '',
            'callbackSentAt' => $query->callback_sent_at ?? null,
            'callbackResponseAt' => $query->callback_response_at ?? null,
        ];
    }

    public static function formatTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d-m-y H:i:s');
        } catch (Throwable) {
            return is_string($value) ? $value : null;
        }
    }

    public static function shortReply(?Response $response, ?Throwable $error): string
    {
        if ($error !== null) {
            return self::truncate('0 | ' . $error->getMessage());
        }

        if ($response === null) {
            return self::truncate('0 | no response');
        }

        $body = trim(preg_replace('/\s+/', ' ', (string) $response->body()) ?? '');

        return self::truncate($response->status() . ($body !== '' ? ' | ' . $body : ''));
    }

    public static function truncate(string $value): string
    {
        if (mb_strlen($value) <= self::RESPONSE_MAX) {
            return $value;
        }

        return mb_substr($value, 0, self::RESPONSE_MAX - 3) . '...';
    }
}
