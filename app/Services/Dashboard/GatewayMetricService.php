<?php

namespace App\Services\Dashboard;

use App\Helpers\GatewayMetricHelper;
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

    /**
     * Classify and record a gateway API response failure.
     */
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

    /**
     * Classify and record a middleware rejection for payin checkout routes.
     */
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

    /**
     * Record checkout timing and mark the request outcome on the request object.
     */
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
            $values = $this->withoutPrefix(fn () => $this->connection()->hgetall($key));

            return $this->normalizeHash(is_array($values) ? $values : []);
        } catch (Throwable $e) {
            $this->logRedisFailure('getMinuteMetrics', $gateway, $e);

            return $this->emptyMetrics();
        }
    }

    /**
     * Aggregate metrics across the configured rolling window for one or all gateways.
     *
     * @param list<string>|null $gateways
     * @return array<string, int|float>
     */
    public function aggregateWindowMetrics(?array $gateways = null, ?int $minutes = null, ?string $flow = null): array
    {
        $minutes = $minutes ?? GatewayMetricHelper::aggregationWindowMinutes();
        $gateways = $gateways ?? GatewayMetricHelper::supportedGateways();
        $totals = $this->emptyMetrics();
        $maxResponseTime = 0;

        foreach ($gateways as $gateway) {
            if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
                continue;
            }

            foreach (GatewayMetricHelper::buildRedisKeysForWindow($gateway, $minutes, $flow) as $key) {
                try {
                    $hash = $this->withoutPrefix(fn () => $this->connection()->hgetall($key));
                    $normalized = $this->normalizeHash(is_array($hash) ? $hash : []);

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

        $series = [
            'success' => array_fill(0, $minutes, 0),
            'pending' => array_fill(0, $minutes, 0),
            'failed' => array_fill(0, $minutes, 0),
            'refunds' => array_fill(0, $minutes, 0),
        ];

        foreach ($gateways as $gateway) {
            if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
                continue;
            }

            foreach (GatewayMetricHelper::buildRedisKeysForWindow($gateway, $minutes, $flow) as $index => $key) {
                try {
                    $hash = $this->withoutPrefix(fn () => $this->connection()->hgetall($key));
                    $normalized = $this->normalizeHash(is_array($hash) ? $hash : []);

                    $series['success'][$index] += $normalized[GatewayMetricHelper::FIELD_SUCCESS];
                    $series['pending'][$index] += $normalized[GatewayMetricHelper::FIELD_PENDING];
                    $series['failed'][$index] += $normalized[GatewayMetricHelper::FIELD_FAILED];
                    $series['refunds'][$index] += $normalized[GatewayMetricHelper::FIELD_REFUNDS];
                } catch (Throwable $e) {
                    $this->logRedisFailure('sparklineSeries', $gateway, $e, ['key' => $key]);
                }
            }
        }

        return $series;
    }

    /**
     * @param array<string, mixed> $hash
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
        return $this->redis ?? Redis::connection();
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
            return $this->withoutPrefix($callback);
        } catch (RedisException|Throwable $e) {
            $this->logRedisFailure($operation, $gateway, $e, $context);

            return null;
        }
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     */
    private function withoutPrefix(callable $callback): mixed
    {
        return Redis::withoutPrefix($callback);
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
