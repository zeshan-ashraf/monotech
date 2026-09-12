<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    /** @var array<string, bool> */
    private static array $columnExists = [];

    /**
     * Stamp the moment we fire the HTTP POST to the client.
     */
    public static function markSending(?Model $transaction): void
    {
        if (! $transaction || ! $transaction->getKey()) {
            return;
        }

        self::safeUpdate($transaction, [
            'callback_sent_at' => now(),
        ]);
    }

    public static function record(
        ?Model $transaction,
        string $callbackStatus,
        ?Response $response = null,
        ?Throwable $error = null
    ): void {
        if (! $transaction || ! $transaction->getKey()) {
            Log::channel('payin')->warning('Payin callback tracker skipped — no transaction', [
                'callback_status' => $callbackStatus,
            ]);

            return;
        }

        $isFinal = in_array(strtolower($callbackStatus), self::FINAL_STATUSES, true);
        $gotReply = $error === null && $response !== null;

        $payload = [
            'callback_response' => self::shortReply($response, $error),
        ];

        // Any HTTP reply for success/failed means we sent it (matches payin logs).
        if ($isFinal && $gotReply) {
            $payload['callback_sent'] = 1;
        }

        if ($gotReply) {
            $payload['callback_response_at'] = now();
        }

        self::safeUpdate($transaction, $payload);
    }

    public static function recordSkipped(?Model $transaction, string $reason): void
    {
        if (! $transaction || ! $transaction->getKey()) {
            return;
        }

        self::safeUpdate($transaction, [
            'callback_response' => self::truncate('skipped | ' . $reason),
        ]);
    }

    /**
     * POST the merchant callback and store the short reply.
     * Sets callback_sent for success/failed when the client returns any HTTP response.
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

    /**
     * Drop unknown columns so a missing timestamp migration cannot block callback_sent.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function safeUpdate(Model $transaction, array $payload): void
    {
        $table = $transaction->getTable();
        $filtered = [];

        foreach ($payload as $column => $value) {
            if (self::tableHasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        if ($filtered === []) {
            return;
        }

        try {
            $transaction->newQuery()
                ->whereKey($transaction->getKey())
                ->update($filtered);
        } catch (Throwable $e) {
            Log::channel('payin')->error('Payin callback tracker update failed', [
                'table' => $table,
                'transaction_id' => $transaction->getKey(),
                'columns' => array_keys($filtered),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function tableHasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (! array_key_exists($key, self::$columnExists)) {
            try {
                self::$columnExists[$key] = Schema::hasColumn($table, $column);
            } catch (Throwable) {
                self::$columnExists[$key] = false;
            }
        }

        return self::$columnExists[$key];
    }
}
