<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApiTrafficHelper;
use App\Helpers\GatewayMetricHelper;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use RedisException;
use Throwable;

/**
 * Records live per-minute API traffic metrics into Redis hashes.
 *
 * All writes are wrapped in try/catch — Redis failures never interrupt requests.
 */
class ApiTrafficService
{
    private const LOG_CHANNEL = 'payin';

    private const METRICS_CONNECTION = 'metrics';

    public function __construct(
        private readonly ?Connection $redis = null
    ) {
    }

    public function recordIncoming(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_INCOMING);
    }

    public function recordAccepted(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_ACCEPTED);
    }

    public function recordRejected(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_REJECTED);
    }

    public function recordGatewayCall(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_GATEWAY_CALLS);
    }

    public function recordCompleted(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_COMPLETED);
    }

    public function recordSuccess(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_SUCCESS);
    }

    public function recordFailure(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_FAILED);
    }

    public function recordPending(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_PENDING);
    }

    public function recordGatewayError(string $apiType, string $type): void
    {
        $field = ApiTrafficHelper::hashFieldForGatewayError($type);

        if ($field !== null) {
            $this->incrementCounter($apiType, $field);
        }

        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_GATEWAY_ERRORS);
    }

    public function recordApplicationError(string $apiType, string $type): void
    {
        $field = ApiTrafficHelper::hashFieldForApplicationError($type);

        if ($field === ApiTrafficHelper::FIELD_INFRASTRUCTURE_ERRORS) {
            $this->recordInfrastructureError($apiType, $type);

            return;
        }

        if ($field !== null) {
            $this->incrementCounter($apiType, $field);
        }

        if ($field !== ApiTrafficHelper::FIELD_APPLICATION_ERRORS) {
            $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_APPLICATION_ERRORS);
        }
    }

    public function recordInfrastructureError(string $apiType, string $type): void
    {
        $field = ApiTrafficHelper::hashFieldForInfrastructureError($type);

        if ($field !== null && $field !== ApiTrafficHelper::FIELD_INFRASTRUCTURE_ERRORS) {
            $this->incrementCounter($apiType, $field);
        }

        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_INFRASTRUCTURE_ERRORS);
    }

    public function recordTimeout(string $apiType): void
    {
        $this->recordInfrastructureError($apiType, GatewayMetricHelper::INFRASTRUCTURE_ERROR_TIMEOUT);
    }

    public function recordResponseTime(string $apiType, int $milliseconds): void
    {
        if ($milliseconds < 0) {
            return;
        }

        $this->safeRedis(function () use ($apiType, $milliseconds): void {
            $key = ApiTrafficHelper::buildRedisKey($apiType);
            $connection = $this->connection();

            $connection->hincrby($key, ApiTrafficHelper::FIELD_TOTAL_RESPONSE_TIME, $milliseconds);
            $connection->hincrby($key, ApiTrafficHelper::FIELD_RESPONSE_SAMPLES, 1);
            $this->updateMaxResponseTime($connection, $key, $milliseconds);
            $this->refreshKeyTtl($connection, $key);
        }, 'recordResponseTime', $apiType);

        if ($milliseconds > ApiTrafficHelper::slowThresholdMs()) {
            $this->recordSlowRequest($apiType);
        }

        if ($milliseconds > ApiTrafficHelper::verySlowThresholdMs()) {
            $this->recordVerySlowRequest($apiType);
        }
    }

    public function recordSlowRequest(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_SLOW_REQUESTS);
    }

    public function recordVerySlowRequest(string $apiType): void
    {
        $this->incrementCounter($apiType, ApiTrafficHelper::FIELD_VERY_SLOW_REQUESTS);
    }

    /**
     * @return array<string, int>
     */
    public function getMinuteMetrics(string $apiType, ?\DateTimeInterface $minute = null): array
    {
        $apiType = ApiTrafficHelper::normalizeApiType($apiType);

        try {
            $key = ApiTrafficHelper::buildRedisKey($apiType, $minute);
            $values = $this->connection()->hgetall($key);

            return $this->normalizeHash(is_array($values) ? $values : []);
        } catch (Throwable $e) {
            $this->logRedisFailure('getMinuteMetrics', $apiType, $e);

            return $this->emptyMetrics();
        }
    }

    /**
     * @param  list<string>|null  $apiTypes
     * @return array<string, int>
     */
    public function aggregateWindowMetrics(?array $apiTypes = null, int $minutes = 5): array
    {
        $minutes = ApiTrafficHelper::normalizeWindowMinutes($minutes);
        $apiTypes = $apiTypes ?? ApiTrafficHelper::apiTypes();
        $totals = $this->emptyMetrics();
        $maxResponseTime = 0;

        foreach ($apiTypes as $apiType) {
            foreach (ApiTrafficHelper::buildRedisKeysForWindow($apiType, $minutes) as $key) {
                try {
                    $hash = $this->connection()->hgetall($key);

                    if (! is_array($hash) || $hash === []) {
                        continue;
                    }

                    $normalized = $this->normalizeHash($hash);

                    foreach ($normalized as $field => $value) {
                        if ($field === ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME) {
                            $maxResponseTime = max($maxResponseTime, $value);

                            continue;
                        }

                        $totals[$field] = ($totals[$field] ?? 0) + $value;
                    }
                } catch (Throwable $e) {
                    $this->logRedisFailure('aggregateWindowMetrics', $apiType, $e, ['key' => $key]);
                }
            }
        }

        $totals[ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME] = $maxResponseTime;

        return $totals;
    }

    /**
     * Per-minute sparkline for incoming requests across all API types.
     *
     * @param  list<string>|null  $apiTypes
     * @return list<int>
     */
    public function incomingSparkline(?array $apiTypes = null, int $minutes = 5): array
    {
        $minutes = ApiTrafficHelper::normalizeWindowMinutes($minutes);
        $apiTypes = $apiTypes ?? ApiTrafficHelper::apiTypes();
        $series = array_fill(0, $minutes, 0);

        foreach ($apiTypes as $apiType) {
            foreach (ApiTrafficHelper::buildRedisKeysForWindow($apiType, $minutes) as $index => $key) {
                try {
                    $hash = $this->connection()->hgetall($key);

                    if (! is_array($hash) || $hash === []) {
                        continue;
                    }

                    $series[$index] += (int) ($hash[ApiTrafficHelper::FIELD_INCOMING] ?? 0);
                } catch (Throwable $e) {
                    $this->logRedisFailure('incomingSparkline', $apiType, $e, ['key' => $key]);
                }
            }
        }

        return $series;
    }

    /**
     * @param array<string, int> $hash
     * @return array<string, int>
     */
    private function normalizeHash(array $hash): array
    {
        $normalized = $this->emptyMetrics();

        foreach (ApiTrafficHelper::aggregatableFields() as $field) {
            if (array_key_exists($field, $hash)) {
                $normalized[$field] = (int) $hash[$field];
            }
        }

        if (array_key_exists(ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME, $hash)) {
            $normalized[ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME] = (int) $hash[ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME];
        }

        return $normalized;
    }

    /**
     * @return array<string, int>
     */
    private function emptyMetrics(): array
    {
        $metrics = [];

        foreach (ApiTrafficHelper::aggregatableFields() as $field) {
            $metrics[$field] = 0;
        }

        $metrics[ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME] = 0;

        return $metrics;
    }

    private function incrementCounter(string $apiType, string $field, int $amount = 1): void
    {
        if ($amount === 0) {
            return;
        }

        $apiType = ApiTrafficHelper::normalizeApiType($apiType);

        $this->safeRedis(function () use ($apiType, $field, $amount): void {
            $key = ApiTrafficHelper::buildRedisKey($apiType);
            $connection = $this->connection();

            $connection->hincrby($key, $field, $amount);
            $this->refreshKeyTtl($connection, $key);
        }, 'incrementCounter', $apiType, [
            'field' => $field,
            'amount' => $amount,
        ]);
    }

    private function updateMaxResponseTime(Connection $connection, string $key, int $durationMs): void
    {
        $currentMax = (int) $connection->hget($key, ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME);

        if ($durationMs > $currentMax) {
            $connection->hset($key, ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME, (string) $durationMs);
        }
    }

    private function refreshKeyTtl(Connection $connection, string $key): void
    {
        $connection->expire($key, ApiTrafficHelper::keyTtlSeconds());
    }

    private function connection(): Connection
    {
        return $this->redis ?? Redis::connection(self::METRICS_CONNECTION);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string, mixed>  $context
     * @return T|null
     */
    private function safeRedis(callable $callback, string $operation, string $apiType, array $context = []): mixed
    {
        try {
            return $callback();
        } catch (RedisException|Throwable $e) {
            $this->logRedisFailure($operation, $apiType, $e, $context);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logRedisFailure(
        string $operation,
        string $apiType,
        Throwable $exception,
        array $context = []
    ): void {
        Log::channel(self::LOG_CHANNEL)->warning('API traffic Redis operation failed', array_merge([
            'operation' => $operation,
            'api_type' => $apiType,
            'error' => $exception->getMessage(),
        ], $context));
    }
}
