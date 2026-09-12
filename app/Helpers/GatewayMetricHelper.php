<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Constants, Redis key builders, and error classification for gateway metrics.
 */
final class GatewayMetricHelper
{
    public const FLOW_PAYIN = 'payin';

    public const CATEGORY_INFRASTRUCTURE = 'infrastructure';

    public const CATEGORY_GATEWAY = 'gateway';

    public const CATEGORY_APPLICATION = 'application';

    public const FIELD_REQUESTS = 'requests';

    public const FIELD_SUCCESS = 'success';

    public const FIELD_PENDING = 'pending';

    public const FIELD_FAILED = 'failed';

    public const FIELD_REJECTED = 'rejected';

    public const FIELD_REFUNDS = 'refunds';

    public const FIELD_GATEWAY_ERRORS = 'gateway_errors';

    public const FIELD_APPLICATION_ERRORS = 'application_errors';

    public const FIELD_INFRASTRUCTURE_ERRORS = 'infrastructure_errors';

    public const FIELD_TIMEOUTS = 'timeouts';

    public const FIELD_SLOW_REQUESTS = 'slow_requests';

    public const FIELD_VERY_SLOW_REQUESTS = 'very_slow_requests';

    public const FIELD_TOTAL_RESPONSE_TIME = 'total_response_time';

    public const FIELD_RESPONSE_SAMPLES = 'response_samples';

    public const FIELD_MAX_RESPONSE_TIME = 'max_response_time';

    public const FIELD_SYSTEM_ERROR = 'system_error';

    public const FIELD_INVALID_ACCOUNT = 'invalid_account';

    public const FIELD_INVALID_ORDER = 'invalid_order';

    public const FIELD_DUPLICATE_ORDER = 'duplicate_order';

    public const FIELD_RULE_VIOLATION = 'rule_violation';

    public const FIELD_VALIDATION_FAILED = 'validation_failed';

    public const GATEWAY_ERROR_SYSTEM = 'system_error';

    public const GATEWAY_ERROR_INVALID_ACCOUNT = 'invalid_account';

    public const GATEWAY_ERROR_INVALID_ORDER = 'invalid_order';

    public const GATEWAY_ERROR_RULE_VIOLATION = 'rule_violation';

    public const GATEWAY_ERROR_DUPLICATE_ORDER = 'duplicate_order';

    public const APPLICATION_ERROR_VALIDATION = 'validation_failed';

    public const APPLICATION_ERROR_MERCHANT_DISABLED = 'merchant_disabled';

    public const APPLICATION_ERROR_PHONE_NOT_VERIFIED = 'phone_not_verified';

    public const APPLICATION_ERROR_DUPLICATE_TRANSACTION = 'duplicate_transaction';

    public const APPLICATION_ERROR_DUPLICATE_ORDER = 'duplicate_order';

    public const APPLICATION_ERROR_UNAUTHORIZED = 'unauthorized';

    public const APPLICATION_ERROR_RULE_VIOLATION = 'rule_violation';

    public const APPLICATION_ERROR_RATE_LIMITED = 'rate_limited';

    public const APPLICATION_ERROR_PENDING_BACKLOG = 'pending_backlog';

    public const APPLICATION_ERROR_BLOCKED_NUMBER = 'blocked_number';

    public const APPLICATION_ERROR_USER_NOT_FOUND = 'user_not_found';

    public const INFRASTRUCTURE_ERROR_TIMEOUT = 'timeout';

    public const INFRASTRUCTURE_ERROR_CONNECTION_TIMEOUT = 'connection_timeout';

    public const INFRASTRUCTURE_ERROR_HTTP_500 = 'http_500';

    public const INFRASTRUCTURE_ERROR_HTTP_502 = 'http_502';

    public const INFRASTRUCTURE_ERROR_HTTP_503 = 'http_503';

    public const INFRASTRUCTURE_ERROR_HTTP_504 = 'http_504';

    public const INFRASTRUCTURE_ERROR_SSL = 'ssl_error';

    public const INFRASTRUCTURE_ERROR_DNS = 'dns_error';

    public const INFRASTRUCTURE_ERROR_CONNECTION = 'connection_error';

    public const REQUEST_ATTR_OUTCOME_RECORDED = 'gateway_metrics_outcome_recorded';

    public const REQUEST_ATTR_START_TIME = 'gateway_metrics_start';

    /** @var array<string, string> */
    private const GATEWAY_ERROR_FIELD_MAP = [
        self::GATEWAY_ERROR_SYSTEM => self::FIELD_SYSTEM_ERROR,
        self::GATEWAY_ERROR_INVALID_ACCOUNT => self::FIELD_INVALID_ACCOUNT,
        self::GATEWAY_ERROR_INVALID_ORDER => self::FIELD_INVALID_ORDER,
        self::GATEWAY_ERROR_RULE_VIOLATION => self::FIELD_RULE_VIOLATION,
        self::GATEWAY_ERROR_DUPLICATE_ORDER => self::FIELD_DUPLICATE_ORDER,
    ];

    /** @var list<string> */
    private const AGGREGATABLE_FIELDS = [
        self::FIELD_REQUESTS,
        self::FIELD_SUCCESS,
        self::FIELD_PENDING,
        self::FIELD_FAILED,
        self::FIELD_REJECTED,
        self::FIELD_REFUNDS,
        self::FIELD_GATEWAY_ERRORS,
        self::FIELD_APPLICATION_ERRORS,
        self::FIELD_INFRASTRUCTURE_ERRORS,
        self::FIELD_TIMEOUTS,
        self::FIELD_SLOW_REQUESTS,
        self::FIELD_VERY_SLOW_REQUESTS,
        self::FIELD_TOTAL_RESPONSE_TIME,
        self::FIELD_RESPONSE_SAMPLES,
        self::FIELD_SYSTEM_ERROR,
        self::FIELD_INVALID_ACCOUNT,
        self::FIELD_INVALID_ORDER,
        self::FIELD_DUPLICATE_ORDER,
        self::FIELD_RULE_VIOLATION,
        self::FIELD_VALIDATION_FAILED,
    ];

    /**
     * @return list<string>
     */
    public static function supportedGateways(): array
    {
        return config('gateway_metrics.gateways', ['easypaisa', 'jazzcash']);
    }

    /**
     * @return array<string, array{name: string, icon: string, brand_color: string}>
     */
    public static function gatewayProfiles(): array
    {
        return config('gateway_metrics.gateway_profiles', []);
    }

    /**
     * @return array{name: string, icon: string, brand_color: string, key: string}
     */
    public static function gatewayProfile(string $gateway): array
    {
        $gateway = self::normalizeGateway($gateway);
        $profiles = self::gatewayProfiles();
        $profile = $profiles[$gateway] ?? [];

        return [
            'key' => $gateway,
            'name' => (string) ($profile['name'] ?? ucfirst($gateway)),
            'icon' => (string) ($profile['icon'] ?? 'fa-credit-card'),
            'brand_color' => (string) ($profile['brand_color'] ?? '#7367f0'),
        ];
    }

    public static function isSupportedGateway(string $gateway): bool
    {
        return in_array(self::normalizeGateway($gateway), self::supportedGateways(), true);
    }

    public static function normalizeGateway(string $gateway): string
    {
        return strtolower(trim($gateway));
    }

    public static function defaultFlow(): string
    {
        return (string) config('gateway_metrics.default_flow', self::FLOW_PAYIN);
    }

    /**
     * Build a per-minute Redis key, e.g. metrics:gateway:easypaisa:payin:202606271430
     */
    public static function buildRedisKey(
        string $gateway,
        ?string $flow = null,
        ?\DateTimeInterface $minute = null
    ): string {
        $gateway = self::normalizeGateway($gateway);
        $flow = $flow ?? self::defaultFlow();
        $minute = $minute !== null
            ? Carbon::instance($minute)->startOfMinute()
            : now()->startOfMinute();

        return sprintf(
            'metrics:gateway:%s:%s:%s',
            $gateway,
            $flow,
            $minute->format('YmdHi')
        );
    }

    /**
     * @return list<string>
     */
    public static function buildRedisKeysForWindow(
        string $gateway,
        int $minutes,
        ?string $flow = null
    ): array {
        $keys = [];
        $cursor = now()->startOfMinute()->subMinutes($minutes - 1);

        for ($index = 0; $index < $minutes; $index++) {
            $keys[] = self::buildRedisKey($gateway, $flow, $cursor->copy()->addMinutes($index));
        }

        return $keys;
    }

    public static function keyTtlSeconds(): int
    {
        return (int) config('gateway_metrics.key_ttl_seconds', 7200);
    }

    public static function aggregationWindowMinutes(): int
    {
        return (int) config('gateway_metrics.aggregation_window_minutes', 60);
    }

    public static function slowThresholdMs(): int
    {
        return (int) config('gateway_metrics.slow_threshold_ms', 3000);
    }

    public static function verySlowThresholdMs(): int
    {
        return (int) config('gateway_metrics.very_slow_threshold_ms', 5000);
    }

    /**
     * @return list<string>
     */
    public static function aggregatableFields(): array
    {
        return self::AGGREGATABLE_FIELDS;
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForGatewayError(string $gatewayErrorType): ?string
    {
        return self::GATEWAY_ERROR_FIELD_MAP[$gatewayErrorType] ?? null;
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForApplicationError(string $applicationErrorType): ?string
    {
        $field = config('gateway_metrics.application_errors.' . $applicationErrorType);

        return is_string($field) && $field !== '' ? $field : null;
    }

    /**
     * @return non-empty-string|null
     */
    public static function hashFieldForInfrastructureError(string $infrastructureErrorType): ?string
    {
        $field = config('gateway_metrics.infrastructure_errors.' . $infrastructureErrorType);

        return is_string($field) && $field !== '' ? $field : null;
    }

    /**
     * @return array{category: string, error_type: string}|null
     */
    public static function classifyGatewayResponse(
        string $gateway,
        ?string $responseCode,
        ?string $responseDescription
    ): ?array {
        $gateway = self::normalizeGateway($gateway);
        $config = config('gateway_metrics.gateway_errors.' . $gateway, []);

        if ($responseCode !== null && $responseCode !== '') {
            $codeMap = $config['response_codes'][$responseCode] ?? null;

            if (is_string($codeMap) && $codeMap !== '') {
                return [
                    'category' => self::CATEGORY_GATEWAY,
                    'error_type' => $codeMap,
                ];
            }
        }

        if ($responseDescription === null || $responseDescription === '') {
            return null;
        }

        $description = strtoupper($responseDescription);
        $descriptionMap = $config['response_descriptions'] ?? [];

        foreach ($descriptionMap as $needle => $errorType) {
            if (str_contains($description, strtoupper((string) $needle))) {
                return [
                    'category' => self::CATEGORY_GATEWAY,
                    'error_type' => $errorType,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{category: string, error_type: string}|null
     */
    public static function classifyHttpStatus(int $httpStatus): ?array
    {
        return match (true) {
            $httpStatus === 500 => [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_HTTP_500,
            ],
            $httpStatus === 502 => [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_HTTP_502,
            ],
            $httpStatus === 503 => [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_HTTP_503,
            ],
            $httpStatus === 504 => [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_HTTP_504,
            ],
            $httpStatus >= 500 => [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_HTTP_500,
            ],
            default => null,
        };
    }

    /**
     * @return array{category: string, error_type: string}|null
     */
    public static function classifyMiddlewareRejection(Request $request, Response $response): ?array
    {
        $statusCode = $response->getStatusCode();
        $payload = self::decodeResponsePayload($response);

        if ($statusCode === 422) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_VALIDATION,
            ];
        }

        if ($statusCode === 429) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_RATE_LIMITED,
            ];
        }

        if ($statusCode === 404 && self::responseMessageContains($payload, 'user not found')) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_USER_NOT_FOUND,
            ];
        }

        if ($statusCode === 400 && self::responseMessageContains($payload, 'blocked')) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_BLOCKED_NUMBER,
            ];
        }

        if ($statusCode === 400 && self::responseMessageContains($payload, 'limit exceeded')) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_MERCHANT_DISABLED,
            ];
        }

        if ($statusCode === 400 && (
            self::responseMessageContains($payload, 'transaction blocked')
            || self::responseMessageContains($payload, 'restriction')
            || self::responseMessageContains($payload, 'limit has been breached')
            || self::responseMessageContains($payload, 'daily transaction limit')
        )) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_RULE_VIOLATION,
            ];
        }

        if ($statusCode === 503 && self::responseMessageContains($payload, 'system outage')) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_PENDING_BACKLOG,
            ];
        }

        $httpClassification = self::classifyHttpStatus($statusCode);

        if ($httpClassification !== null) {
            return $httpClassification;
        }

        if ($statusCode >= 400) {
            return [
                'category' => self::CATEGORY_APPLICATION,
                'error_type' => self::APPLICATION_ERROR_RULE_VIOLATION,
            ];
        }

        return null;
    }

    /**
     * @return array{category: string, error_type: string}
     */
    public static function classifyConnectionExceptionMessage(string $message): array
    {
        $normalized = strtolower($message);

        if (str_contains($normalized, 'timed out') || str_contains($normalized, 'timeout')) {
            return [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_TIMEOUT,
            ];
        }

        if (str_contains($normalized, 'ssl') || str_contains($normalized, 'certificate')) {
            return [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_SSL,
            ];
        }

        if (str_contains($normalized, 'could not resolve host') || str_contains($normalized, 'dns')) {
            return [
                'category' => self::CATEGORY_INFRASTRUCTURE,
                'error_type' => self::INFRASTRUCTURE_ERROR_DNS,
            ];
        }

        return [
            'category' => self::CATEGORY_INFRASTRUCTURE,
            'error_type' => self::INFRASTRUCTURE_ERROR_CONNECTION,
        ];
    }

    public static function isPayinCheckoutRequest(Request $request): bool
    {
        if (! $request->isMethod('POST')) {
            return false;
        }

        return $request->is(
            'api/payin/checkout',
            'api/v1/payment-checkout',
            'api/v1/payin-checkout',
            'payin/checkout',
            'v1/payment-checkout',
            'v1/payin-checkout'
        );
    }

    /**
     * Resolve the checkout gateway from common request payload keys.
     */
    public static function resolveCheckoutGateway(Request $request): ?string
    {
        foreach ([
            $request->input('payment_method'),
            $request->input('txn_type'),
            $request->input('gateway'),
        ] as $candidate) {
            $gateway = self::normalizeGateway((string) $candidate);

            if ($gateway !== '' && self::isSupportedGateway($gateway)) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Map legacy Redis hash fields to the current schema.
     *
     * @param array<string, mixed> $hash
     * @return array<string, int>
     */
    public static function normalizeLegacyHash(array $hash): array
    {
        $legacyMap = [
            'total_requests' => self::FIELD_REQUESTS,
            'successful_requests' => self::FIELD_SUCCESS,
            'rejected_requests' => self::FIELD_REJECTED,
            'response_time_total_ms' => self::FIELD_TOTAL_RESPONSE_TIME,
            'response_time_count' => self::FIELD_RESPONSE_SAMPLES,
            'max_response_time_ms' => self::FIELD_MAX_RESPONSE_TIME,
            'system_errors' => self::FIELD_SYSTEM_ERROR,
            'rule_violations' => self::FIELD_RULE_VIOLATION,
            'validation_errors' => self::FIELD_VALIDATION_FAILED,
        ];

        $normalized = [];

        foreach ($hash as $field => $value) {
            $targetField = $legacyMap[$field] ?? (is_string($field) ? $field : null);

            if ($targetField === null) {
                continue;
            }

            $normalized[$targetField] = ($normalized[$targetField] ?? 0) + (int) $value;
        }

        return $normalized;
    }

    public static function isCurrentFormatKey(string $key): bool
    {
        return str_starts_with($key, 'metrics:gateway:');
    }

    public static function isLegacyFormatKey(string $key): bool
    {
        return (bool) preg_match('/^gateway:[^:]+:\d{4}-\d{2}-\d{2}:\d{2}:\d{2}$/', $key);
    }

    /**
     * @return list<string>
     */
    public static function discoverMetricKeyPatterns(): array
    {
        return [
            'metrics:gateway:*:payin:*',
            'gateway:*:*:*:*',
        ];
    }

    public static function formatDurationSeconds(int|float $milliseconds): string
    {
        $seconds = $milliseconds / 1000;

        if ($seconds < 1) {
            return number_format($seconds, 2) . ' sec';
        }

        return number_format($seconds, 2) . ' sec';
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeResponsePayload(Response $response): array
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function responseMessageContains(array $payload, string $needle): bool
    {
        $message = strtolower((string) ($payload['message'] ?? ''));

        return $message !== '' && str_contains($message, strtolower($needle));
    }
}
