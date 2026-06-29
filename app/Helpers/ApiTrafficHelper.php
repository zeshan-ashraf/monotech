<?php

declare(strict_types=1);

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * API traffic classification, Redis key builders, and metric field constants.
 */
final class ApiTrafficHelper
{
    public const FIELD_INCOMING = 'incoming';

    public const FIELD_ACCEPTED = 'accepted';

    public const FIELD_REJECTED = 'rejected';

    public const FIELD_GATEWAY_CALLS = 'gateway_calls';

    public const FIELD_COMPLETED = 'completed';

    public const FIELD_SUCCESS = 'success';

    public const FIELD_FAILED = 'failed';

    public const FIELD_PENDING = 'pending';

    public const FIELD_TIMEOUTS = 'timeouts';

    public const FIELD_GATEWAY_ERRORS = 'gateway_errors';

    public const FIELD_APPLICATION_ERRORS = 'application_errors';

    public const FIELD_INFRASTRUCTURE_ERRORS = 'infrastructure_errors';

    public const FIELD_TOTAL_RESPONSE_TIME = 'total_response_time';

    public const FIELD_RESPONSE_SAMPLES = 'response_samples';

    public const FIELD_MAX_RESPONSE_TIME = 'max_response_time';

    public const FIELD_SLOW_REQUESTS = 'slow_requests';

    public const FIELD_VERY_SLOW_REQUESTS = 'very_slow_requests';

    public const FIELD_SYSTEM_ERROR = 'system_error';

    public const FIELD_INVALID_ACCOUNT = 'invalid_account';

    public const FIELD_INVALID_ORDER = 'invalid_order';

    public const FIELD_RULE_VIOLATION = 'rule_violation';

    public const FIELD_DUPLICATE_ORDER = 'duplicate_order';

    public const FIELD_VALIDATION_FAILED = 'validation_failed';

    public const REQUEST_ATTR_API_TYPE = 'api_traffic_type';

    public const REQUEST_ATTR_START_TIME = 'api_traffic_start_time';

    public const REQUEST_ATTR_OUTCOME_RECORDED = 'api_traffic_outcome_recorded';

    /** @var list<string> */
    private const AGGREGATABLE_FIELDS = [
        self::FIELD_INCOMING,
        self::FIELD_ACCEPTED,
        self::FIELD_REJECTED,
        self::FIELD_GATEWAY_CALLS,
        self::FIELD_COMPLETED,
        self::FIELD_SUCCESS,
        self::FIELD_FAILED,
        self::FIELD_PENDING,
        self::FIELD_TIMEOUTS,
        self::FIELD_GATEWAY_ERRORS,
        self::FIELD_APPLICATION_ERRORS,
        self::FIELD_INFRASTRUCTURE_ERRORS,
        self::FIELD_TOTAL_RESPONSE_TIME,
        self::FIELD_RESPONSE_SAMPLES,
        self::FIELD_SLOW_REQUESTS,
        self::FIELD_VERY_SLOW_REQUESTS,
        self::FIELD_SYSTEM_ERROR,
        self::FIELD_INVALID_ACCOUNT,
        self::FIELD_INVALID_ORDER,
        self::FIELD_RULE_VIOLATION,
        self::FIELD_DUPLICATE_ORDER,
        self::FIELD_VALIDATION_FAILED,
    ];

    /**
     * Resolve the API type for an incoming request using route name, then path patterns.
     */
    public static function classifyRequest(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (is_string($routeName) && $routeName !== '') {
            foreach (self::routeNamePatterns() as $pattern => $apiType) {
                if (fnmatch($pattern, $routeName)) {
                    return self::normalizeApiType($apiType);
                }
            }
        }

        $path = trim($request->path(), '/');

        foreach (self::pathPatterns() as $pattern => $apiType) {
            if (preg_match($pattern, $path) === 1) {
                return self::normalizeApiType($apiType);
            }
        }

        return self::defaultApiType();
    }

    public static function normalizeApiType(string $apiType): string
    {
        $apiType = strtolower(trim($apiType));

        return in_array($apiType, self::apiTypes(), true) ? $apiType : self::defaultApiType();
    }

    /**
     * Build a per-minute Redis key, e.g. metrics:traffic:payin:202606281430
     */
    public static function buildRedisKey(string $apiType, ?\DateTimeInterface $minute = null): string
    {
        $apiType = self::normalizeApiType($apiType);
        $minute = $minute !== null
            ? Carbon::instance($minute)->startOfMinute()
            : now()->startOfMinute();

        return sprintf(
            'metrics:traffic:%s:%s',
            $apiType,
            $minute->format('YmdHi')
        );
    }

    /**
     * @return list<string>
     */
    public static function buildRedisKeysForWindow(string $apiType, int $minutes): array
    {
        $keys = [];
        $cursor = now()->startOfMinute()->subMinutes($minutes - 1);

        for ($index = 0; $index < $minutes; $index++) {
            $keys[] = self::buildRedisKey($apiType, $cursor->copy()->addMinutes($index));
        }

        return $keys;
    }

    /**
     * @return list<string>
     */
    public static function apiTypes(): array
    {
        return config('api_traffic.api_types', ['other']);
    }

    public static function defaultApiType(): string
    {
        return (string) config('api_traffic.default_api_type', 'other');
    }

    public static function keyTtlSeconds(): int
    {
        return (int) config('api_traffic.key_ttl_seconds', 7200);
    }

    public static function slowThresholdMs(): int
    {
        return (int) config('api_traffic.slow_threshold_ms', 3000);
    }

    public static function verySlowThresholdMs(): int
    {
        return (int) config('api_traffic.very_slow_threshold_ms', 5000);
    }

    /**
     * @return list<int>
     */
    public static function allowedWindows(): array
    {
        return config('api_traffic.allowed_windows', [1, 5, 10, 30, 60]);
    }

    public static function normalizeWindowMinutes(int $minutes): int
    {
        $allowed = self::allowedWindows();

        return in_array($minutes, $allowed, true)
            ? $minutes
            : (int) config('api_traffic.default_window_minutes', 5);
    }

    /**
     * @return list<string>
     */
    public static function aggregatableFields(): array
    {
        return self::AGGREGATABLE_FIELDS;
    }

    /**
     * @return array<string, string>
     */
    public static function routeNamePatterns(): array
    {
        return config('api_traffic.route_name_patterns', []);
    }

    /**
     * @return array<string, string>
     */
    public static function pathPatterns(): array
    {
        return config('api_traffic.path_patterns', []);
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForGatewayError(string $gatewayErrorType): ?string
    {
        $field = config('api_traffic.gateway_errors.' . $gatewayErrorType);

        return is_string($field) && $field !== '' ? $field : null;
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForApplicationError(string $applicationErrorType): ?string
    {
        $field = config('api_traffic.application_errors.' . $applicationErrorType);

        return is_string($field) && $field !== '' ? $field : null;
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForInfrastructureError(string $infrastructureErrorType): ?string
    {
        $field = config('api_traffic.infrastructure_errors.' . $infrastructureErrorType);

        return is_string($field) && $field !== '' ? $field : null;
    }

    public static function resolveApiTypeFromRequest(Request $request): string
    {
        $apiType = $request->attributes->get(self::REQUEST_ATTR_API_TYPE);

        return is_string($apiType) && $apiType !== ''
            ? self::normalizeApiType($apiType)
            : self::classifyRequest($request);
    }

    public static function formatDurationSeconds(int|float $milliseconds): string
    {
        $seconds = $milliseconds / 1000;

        if ($seconds < 1) {
            return number_format($seconds, 2) . ' sec';
        }

        return number_format($seconds, 2) . ' sec';
    }
}
