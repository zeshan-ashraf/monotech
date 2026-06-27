<?php

namespace App\Services\Dashboard;

use App\Helpers\GatewayMetricHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use RedisException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records live per-minute gateway payment metrics into Redis hashes.
 */
class GatewayMetricService
{
    private const LOG_CHANNEL = 'payin';

    private const METRICS_CONNECTION = 'metrics';

    public function __construct(
        private readonly ?Connection $redis = null
    ) {
    }

    public function recordRequest(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_REQUESTS, $flow);
    }

    public function recordSuccess(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_SUCCESS, $flow);
    }

    public function recordPending(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_PENDING, $flow);
    }

    public function recordFailed(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_FAILED, $flow);
    }

    public function recordRejected(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_REJECTED, $flow);
    }

    public function recordRefund(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_REFUNDS, $flow);
    }

    public function recordGatewayError(string $gateway, string $type, ?string $flow = null): void
    {
        $field = GatewayMetricHelper::hashFieldForGatewayError($type);

        if ($field !== null) {
            $this->incrementCounter($gateway, $field, $flow);
        }

        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_GATEWAY_ERRORS, $flow);
    }

    public function recordApplicationError(string $gateway, string $type, ?string $flow = null): void
    {
        $field = GatewayMetricHelper::hashFieldForApplicationError($type);

        if ($field === GatewayMetricHelper::FIELD_INFRASTRUCTURE_ERRORS) {
            $this->recordInfrastructureError($gateway, $type, $flow);

            return;
        }

        if ($field !== null) {
            $this->incrementCounter($gateway, $field, $flow);
        }

        if ($field !== GatewayMetricHelper::FIELD_APPLICATION_ERRORS) {
            $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_APPLICATION_ERRORS, $flow);
        }
    }

    public function recordInfrastructureError(string $gateway, string $type, ?string $flow = null): void
    {
        $field = GatewayMetricHelper::hashFieldForInfrastructureError($type);

        if ($field !== null && $field !== GatewayMetricHelper::FIELD_INFRASTRUCTURE_ERRORS) {
            $this->incrementCounter($gateway, $field, $flow);
        }

        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_INFRASTRUCTURE_ERRORS, $flow);
    }

    public function recordResponseTime(string $gateway, int $milliseconds, ?string $flow = null): void
    {
        if ($milliseconds < 0) {
            return;
        }

        $this->safeRedis(function () use ($gateway, $milliseconds, $flow): void {
            $key = GatewayMetricHelper::buildRedisKey($gateway, $flow);
            $connection = $this->connection();

            $connection->hincrby($key, GatewayMetricHelper::FIELD_TOTAL_RESPONSE_TIME, $milliseconds);
            $connection->hincrby($key, GatewayMetricHelper::FIELD_RESPONSE_SAMPLES, 1);
            $this->updateMaxResponseTime($connection, $key, $milliseconds);
            $this->refreshKeyTtl($connection, $key);
        }, 'recordResponseTime', $gateway);

        if ($milliseconds > GatewayMetricHelper::slowThresholdMs()) {
            $this->recordSlowRequest($gateway, $flow);
        }

        if ($milliseconds > GatewayMetricHelper::verySlowThresholdMs()) {
            $this->recordVerySlowRequest($gateway, $flow);
        }
    }

    public function recordTimeout(string $gateway, ?string $flow = null): void
    {
        $this->recordInfrastructureError($gateway, GatewayMetricHelper::INFRASTRUCTURE_ERROR_TIMEOUT, $flow);
    }

    public function recordSlowRequest(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_SLOW_REQUESTS, $flow);
    }

    public function recordVerySlowRequest(string $gateway, ?string $flow = null): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_VERY_SLOW_REQUESTS, $flow);
    }

    public function recordGatewayResponseFailure(
        string $gateway,
        ?string $responseCode,
        ?string $responseDescription,
        ?string $flow = null
    ): void {
        $this->recordFailed($gateway, $flow);

        $classification = GatewayMetricHelper::classifyGatewayResponse(
            $gateway,
            $responseCode,
            $responseDescription
        );

        if ($classification !== null) {
            $this->recordGatewayError($gateway, $classification['error_type'], $flow);
        }
    }

    public function recordMiddlewareRejection(
        string $gateway,
        Request $request,
        Response $response,
        ?string $flow = null
    ): void {
        $this->recordRejected($gateway, $flow);

        $classification = GatewayMetricHelper::classifyMiddlewareRejection($request, $response);

        if ($classification === null) {
            return;
        }

        match ($classification['category']) {
            GatewayMetricHelper::CATEGORY_INFRASTRUCTURE => $this->recordInfrastructureError(
                $gateway,
                $classification['error_type'],
                $flow
            ),
            GatewayMetricHelper::CATEGORY_GATEWAY => $this->recordGatewayError(
                $gateway,
                $classification['error_type'],
                $flow
            ),
            GatewayMetricHelper::CATEGORY_APPLICATION => $this->recordApplicationError(
                $gateway,
                $classification['error_type'],
                $flow
            ),
            default => null,
        };
    }

    public function finalizeCheckoutMetrics(Request $request, string $gateway, float $startTime, ?string $flow = null): void
    {
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $this->recordResponseTime($gateway, $durationMs, $flow);
        $request->attributes->set(GatewayMetricHelper::REQUEST_ATTR_OUTCOME_RECORDED, true);
    }

    /**
     * @return array<string, int>
     */
    public function getMinuteMetrics(string $gateway, ?\DateTimeInterface $minute = null, ?string $flow = null): array
    {
        $gateway = GatewayMetricHelper::normalizeGateway($gateway);

        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return $this->emptyMetrics();
        }

        try {
            $key = GatewayMetricHelper::buildRedisKey($gateway, $flow, $minute);
            $values = $this->connection()->hgetall($key);

            return $this->normalizeHash(is_array($values) ? $values : []);
        } catch (Throwable $e) {
            $this->logRedisFailure('getMinuteMetrics', $gateway, $e);

            return $this->emptyMetrics();
        }
    }

    /**
     * @param list<string>|null $gateways
     * @return array<string, int|float>
     */
    public function aggregateWindowMetrics(?array $gateways = null, ?int $minutes = null, ?string $flow = null): array
    {
        $minutes = $minutes ?? GatewayMetricHelper::aggregationWindowMinutes();
        $gateways = $gateways ?? GatewayMetricHelper::supportedGateways();
        $flow = $flow ?? GatewayMetricHelper::defaultFlow();
        $totals = $this->emptyMetrics();
        $maxResponseTime = 0;
        $processedKeys = [];

        foreach ($gateways as $gateway) {
            if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
                continue;
            }

            foreach ($this->resolveKeysForWindow($gateway, $minutes, $flow) as $key) {
                if (isset($processedKeys[$key])) {
                    continue;
                }

                $processedKeys[$key] = true;

                try {
                    $hash = $this->connection()->hgetall($key);

                    if (! is_array($hash) || $hash === []) {
                        continue;
                    }

                    $normalized = GatewayMetricHelper::isLegacyFormatKey($key)
                        ? $this->mergeMetrics($this->emptyMetrics(), GatewayMetricHelper::normalizeLegacyHash($hash))
                        : $this->normalizeHash($hash);

                    foreach ($normalized as $field => $value) {
                        if ($field === GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME) {
                            $maxResponseTime = max($maxResponseTime, $value);

                            continue;
                        }

                        $totals[$field] = ($totals[$field] ?? 0) + $value;
                    }
                } catch (Throwable $e) {
                    $this->logRedisFailure('aggregateWindowMetrics', $gateway, $e, ['key' => $key]);
                }
            }
        }

        $totals[GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME] = $maxResponseTime;

        return $totals;
    }

    /**
     * @param list<string>|null $gateways
     * @return array<string, list<int>>
     */
    public function sparklineSeries(?array $gateways = null, ?int $minutes = null, ?string $flow = null): array
    {
        $minutes = $minutes ?? GatewayMetricHelper::aggregationWindowMinutes();
        $gateways = $gateways ?? GatewayMetricHelper::supportedGateways();
        $flow = $flow ?? GatewayMetricHelper::defaultFlow();

        $series = [
            'success' => array_fill(0, $minutes, 0),
            'pending' => array_fill(0, $minutes, 0),
            'failed' => array_fill(0, $minutes, 0),
            'rejected' => array_fill(0, $minutes, 0),
        ];

        foreach ($gateways as $gateway) {
            if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
                continue;
            }

            foreach (GatewayMetricHelper::buildRedisKeysForWindow($gateway, $minutes, $flow) as $index => $key) {
                try {
                    $hash = $this->connection()->hgetall($key);

                    if (! is_array($hash) || $hash === []) {
                        continue;
                    }

                    $normalized = $this->normalizeHash($hash);

                    $series['success'][$index] += $normalized[GatewayMetricHelper::FIELD_SUCCESS];
                    $series['pending'][$index] += $normalized[GatewayMetricHelper::FIELD_PENDING];
                    $series['failed'][$index] += $normalized[GatewayMetricHelper::FIELD_FAILED];
                    $series['rejected'][$index] += $normalized[GatewayMetricHelper::FIELD_REJECTED];
                } catch (Throwable $e) {
                    $this->logRedisFailure('sparklineSeries', $gateway, $e, ['key' => $key]);
                }
            }
        }

        return $series;
    }

    /**
     * @return list<string>
     */
    private function resolveKeysForWindow(string $gateway, int $minutes, string $flow): array
    {
        $keys = GatewayMetricHelper::buildRedisKeysForWindow($gateway, $minutes, $flow);
        $windowStart = now()->startOfMinute()->subMinutes($minutes - 1);

        foreach ($this->discoverRedisKeys() as $key) {
            if (! $this->keyBelongsToGateway($key, $gateway)) {
                continue;
            }

            if (! $this->keyWithinWindow($key, $windowStart)) {
                continue;
            }

            $keys[] = $key;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    private function discoverRedisKeys(): array
    {
        $keys = [];

        try {
            foreach (GatewayMetricHelper::discoverMetricKeyPatterns() as $pattern) {
                $matches = $this->connection()->keys($pattern);

                if (is_array($matches)) {
                    foreach ($matches as $match) {
                        if (is_string($match) && $match !== '') {
                            $keys[] = $match;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $this->logRedisFailure('discoverRedisKeys', 'all', $e);
        }

        return $keys;
    }

    private function keyBelongsToGateway(string $key, string $gateway): bool
    {
        $gateway = GatewayMetricHelper::normalizeGateway($gateway);

        if (GatewayMetricHelper::isCurrentFormatKey($key)) {
            return str_contains($key, ':gateway:' . $gateway . ':');
        }

        if (GatewayMetricHelper::isLegacyFormatKey($key)) {
            return str_starts_with($key, 'gateway:' . $gateway . ':');
        }

        return false;
    }

    private function keyWithinWindow(string $key, Carbon $windowStart): bool
    {
        $bucket = $this->extractBucketTime($key);

        if ($bucket === null) {
            return false;
        }

        return $bucket->greaterThanOrEqualTo($windowStart)
            && $bucket->lessThanOrEqualTo(now()->startOfMinute());
    }

    private function extractBucketTime(string $key): ?Carbon
    {
        if (preg_match('/:payin:(\d{12})$/', $key, $matches)) {
            return Carbon::createFromFormat('YmdHi', $matches[1]) ?: null;
        }

        if (preg_match('/^gateway:[^:]+:(\d{4}-\d{2}-\d{2}):(\d{2}):(\d{2})$/', $key, $matches)) {
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                $matches[1] . ' ' . $matches[2] . ':' . $matches[3]
            ) ?: null;
        }

        return null;
    }

    /**
     * @param array<string, int> $hash
     * @return array<string, int>
     */
    private function normalizeHash(array $hash): array
    {
        $normalized = $this->emptyMetrics();

        foreach (GatewayMetricHelper::aggregatableFields() as $field) {
            if (array_key_exists($field, $hash)) {
                $normalized[$field] = (int) $hash[$field];
            }
        }

        if (array_key_exists(GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME, $hash)) {
            $normalized[GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME] = (int) $hash[GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME];
        }

        return $normalized;
    }

    /**
     * @param array<string, int> $base
     * @param array<string, int> $addition
     * @return array<string, int>
     */
    private function mergeMetrics(array $base, array $addition): array
    {
        foreach ($addition as $field => $value) {
            $base[$field] = ($base[$field] ?? 0) + $value;
        }

        return $base;
    }

    /**
     * @return array<string, int>
     */
    private function emptyMetrics(): array
    {
        $metrics = [];

        foreach (GatewayMetricHelper::aggregatableFields() as $field) {
            $metrics[$field] = 0;
        }

        $metrics[GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME] = 0;

        return $metrics;
    }

    private function incrementCounter(string $gateway, string $field, ?string $flow = null, int $amount = 1): void
    {
        if ($amount === 0) {
            return;
        }

        $gateway = GatewayMetricHelper::normalizeGateway($gateway);

        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->safeRedis(function () use ($gateway, $field, $flow, $amount): void {
            $key = GatewayMetricHelper::buildRedisKey($gateway, $flow);
            $connection = $this->connection();

            $connection->hincrby($key, $field, $amount);
            $this->refreshKeyTtl($connection, $key);
        }, 'incrementCounter', $gateway, [
            'field' => $field,
            'amount' => $amount,
        ]);
    }

    private function updateMaxResponseTime(Connection $connection, string $key, int $durationMs): void
    {
        $currentMax = (int) $connection->hget($key, GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME);

        if ($durationMs > $currentMax) {
            $connection->hset($key, GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME, (string) $durationMs);
        }
    }

    private function refreshKeyTtl(Connection $connection, string $key): void
    {
        $connection->expire($key, GatewayMetricHelper::keyTtlSeconds());
    }

    private function connection(): Connection
    {
        return $this->redis ?? Redis::connection(self::METRICS_CONNECTION);
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     * @param array<string, mixed> $context
     * @return T|null
     */
    private function safeRedis(callable $callback, string $operation, string $gateway, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (RedisException|Throwable $e) {
            $this->logRedisFailure($operation, $gateway, $e, $context);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logRedisFailure(
        string $operation,
        string $gateway,
        Throwable $exception,
        array $context = []
    ): void {
        Log::channel(self::LOG_CHANNEL)->warning('Gateway metrics Redis operation failed', array_merge([
            'operation' => $operation,
            'gateway' => $gateway,
            'error' => $exception->getMessage(),
        ], $context));
    }
}
