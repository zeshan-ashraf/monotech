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

    public function recordRequest(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_TOTAL_REQUESTS);
    }

    public function recordSuccess(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_SUCCESSFUL_REQUESTS);
    }

    public function recordPending(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_PENDING);
    }

    public function recordFailure(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_REJECTED_REQUESTS);
    }

    public function recordTimeout(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_TIMEOUTS);
        $this->recordFailure($gateway);
    }

    public function recordInfrastructureError(string $gateway, string $infrastructureErrorType): void
    {
        $field = GatewayMetricHelper::hashFieldForInfrastructureError($infrastructureErrorType);

        if ($field !== null) {
            $this->incrementCounter($gateway, $field);
        }

        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_INFRASTRUCTURE_ERRORS);
        $this->recordFailure($gateway);
    }

    public function recordGatewayError(string $gateway, string $gatewayErrorType): void
    {
        $field = GatewayMetricHelper::hashFieldForGatewayError($gatewayErrorType);

        if ($field !== null) {
            $this->incrementCounter($gateway, $field);
        }

        $this->recordFailure($gateway);
    }

    public function recordApplicationError(string $gateway, string $applicationErrorType): void
    {
        $field = GatewayMetricHelper::hashFieldForApplicationError($applicationErrorType);

        if ($field !== null) {
            $this->incrementCounter($gateway, $field);
        }

        if ($field !== GatewayMetricHelper::FIELD_VALIDATION_ERRORS) {
            $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_APPLICATION_ERRORS);
        }

        $this->recordFailure($gateway);
    }

    public function recordResponseTime(string $gateway, float $durationMs): void
    {
        if ($durationMs < 0) {
            return;
        }

        $roundedDuration = (int) round($durationMs);

        $this->safeRedis(function () use ($gateway, $roundedDuration): void {
            $key = GatewayMetricHelper::buildRedisKey($gateway);
            $connection = $this->connection();

            $connection->hincrby($key, GatewayMetricHelper::FIELD_RESPONSE_TIME_TOTAL_MS, $roundedDuration);
            $connection->hincrby($key, GatewayMetricHelper::FIELD_RESPONSE_TIME_COUNT, 1);
            $this->updateMaxResponseTime($connection, $key, $roundedDuration);
            $this->refreshKeyTtl($connection, $key);
        }, 'recordResponseTime', $gateway);

        if ($roundedDuration > GatewayMetricHelper::slowThresholdMs()) {
            $this->recordSlowRequest($gateway);
        }

        if ($roundedDuration > GatewayMetricHelper::verySlowThresholdMs()) {
            $this->recordVerySlowRequest($gateway);
        }
    }

    public function recordSlowRequest(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_SLOW_REQUESTS);
    }

    public function recordVerySlowRequest(string $gateway): void
    {
        $this->incrementCounter($gateway, GatewayMetricHelper::FIELD_VERY_SLOW_REQUESTS);
    }

    /**
     * Classify and record a gateway API response failure.
     */
    public function recordGatewayResponseFailure(
        string $gateway,
        ?string $responseCode,
        ?string $responseDescription
    ): void {
        $classification = GatewayMetricHelper::classifyGatewayResponse(
            $gateway,
            $responseCode,
            $responseDescription
        );

        if ($classification === null) {
            $this->recordFailure($gateway);

            return;
        }

        $this->recordGatewayError($gateway, $classification['error_type']);
    }

    /**
     * Classify and record a middleware rejection for payin checkout routes.
     */
    public function recordMiddlewareRejection(
        string $gateway,
        Request $request,
        Response $response
    ): void {
        $classification = GatewayMetricHelper::classifyMiddlewareRejection($request, $response);

        if ($classification === null) {
            $this->recordFailure($gateway);

            return;
        }

        $this->recordClassifiedError(
            $gateway,
            $classification['category'],
            $classification['error_type']
        );
    }

    /**
     * Record checkout timing and mark the request outcome on the request object.
     */
    public function finalizeCheckoutMetrics(Request $request, string $gateway, float $startTime): void
    {
        $durationMs = (microtime(true) - $startTime) * 1000;
        $this->recordResponseTime($gateway, $durationMs);
        $request->attributes->set(GatewayMetricHelper::REQUEST_ATTR_OUTCOME_RECORDED, true);
    }

    /**
     * @return array<string, int|string>|null
     */
    public function getMinuteMetrics(string $gateway, ?\DateTimeInterface $minute = null): ?array
    {
        $gateway = GatewayMetricHelper::normalizeGateway($gateway);

        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return null;
        }

        try {
            $key = GatewayMetricHelper::buildRedisKey($gateway, $minute);
            $values = $this->withoutPrefix(fn () => $this->connection()->hgetall($key));

            return is_array($values) && $values !== [] ? $values : null;
        } catch (Throwable $e) {
            $this->logRedisFailure('getMinuteMetrics', $gateway, $e);

            return null;
        }
    }

    private function recordClassifiedError(string $gateway, string $category, string $errorType): void
    {
        match ($category) {
            GatewayMetricHelper::CATEGORY_INFRASTRUCTURE => $this->recordInfrastructureError($gateway, $errorType),
            GatewayMetricHelper::CATEGORY_GATEWAY => $this->recordGatewayError($gateway, $errorType),
            GatewayMetricHelper::CATEGORY_APPLICATION => $this->recordApplicationError($gateway, $errorType),
            default => $this->recordFailure($gateway),
        };
    }

    private function incrementCounter(string $gateway, string $field, int $amount = 1): void
    {
        if ($amount === 0) {
            return;
        }

        $gateway = GatewayMetricHelper::normalizeGateway($gateway);

        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->safeRedis(function () use ($gateway, $field, $amount): void {
            $key = GatewayMetricHelper::buildRedisKey($gateway);
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
        $currentMax = (int) $connection->hget($key, GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME_MS);

        if ($durationMs > $currentMax) {
            $connection->hset($key, GatewayMetricHelper::FIELD_MAX_RESPONSE_TIME_MS, (string) $durationMs);
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
