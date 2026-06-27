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
    public const GATEWAY_EASYPAISA = 'easypaisa';

    public const GATEWAY_JAZZCASH = 'jazzcash';

    public const CATEGORY_INFRASTRUCTURE = 'infrastructure';

    public const CATEGORY_GATEWAY = 'gateway';

    public const CATEGORY_APPLICATION = 'application';

    public const FIELD_TOTAL_REQUESTS = 'total_requests';

    public const FIELD_SUCCESSFUL_REQUESTS = 'successful_requests';

    public const FIELD_REJECTED_REQUESTS = 'rejected_requests';

    public const FIELD_PENDING = 'pending';

    public const FIELD_TIMEOUTS = 'timeouts';

    public const FIELD_HTTP_ERRORS = 'http_errors';

    public const FIELD_SYSTEM_ERRORS = 'system_errors';

    public const FIELD_INVALID_ACCOUNT = 'invalid_account';

    public const FIELD_INVALID_ORDER = 'invalid_order';

    public const FIELD_DUPLICATE_ORDER = 'duplicate_order';

    public const FIELD_RULE_VIOLATIONS = 'rule_violations';

    public const FIELD_VALIDATION_ERRORS = 'validation_errors';

    public const FIELD_APPLICATION_ERRORS = 'application_errors';

    public const FIELD_INFRASTRUCTURE_ERRORS = 'infrastructure_errors';

    public const FIELD_RESPONSE_TIME_TOTAL_MS = 'response_time_total_ms';

    public const FIELD_RESPONSE_TIME_COUNT = 'response_time_count';

    public const FIELD_MAX_RESPONSE_TIME_MS = 'max_response_time_ms';

    public const FIELD_SLOW_REQUESTS = 'slow_requests';

    public const FIELD_VERY_SLOW_REQUESTS = 'very_slow_requests';

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

    public const APPLICATION_ERROR_USER_NOT_FOUND = 'user_not_found';

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

    private const GATEWAY_ERROR_FIELD_MAP = [
        self::GATEWAY_ERROR_SYSTEM => self::FIELD_SYSTEM_ERRORS,
        self::GATEWAY_ERROR_INVALID_ACCOUNT => self::FIELD_INVALID_ACCOUNT,
        self::GATEWAY_ERROR_INVALID_ORDER => self::FIELD_INVALID_ORDER,
        self::GATEWAY_ERROR_RULE_VIOLATION => self::FIELD_RULE_VIOLATIONS,
        self::GATEWAY_ERROR_DUPLICATE_ORDER => self::FIELD_DUPLICATE_ORDER,
    ];

    /**
     * @return list<string>
     */
    public static function supportedGateways(): array
    {
        return config('gateway_metrics.gateways', [
            self::GATEWAY_EASYPAISA,
            self::GATEWAY_JAZZCASH,
        ]);
    }

    public static function isSupportedGateway(string $gateway): bool
    {
        return in_array(self::normalizeGateway($gateway), self::supportedGateways(), true);
    }

    public static function normalizeGateway(string $gateway): string
    {
        return strtolower(trim($gateway));
    }

    /**
     * Build a per-minute Redis key, e.g. gateway:easypaisa:2026-06-27:14:31
     */
    public static function buildRedisKey(string $gateway, ?\DateTimeInterface $minute = null): string
    {
        $gateway = self::normalizeGateway($gateway);
        $minute = $minute !== null
            ? Carbon::instance($minute)->startOfMinute()
            : now()->startOfMinute();

        return sprintf(
            'gateway:%s:%s:%s:%s',
            $gateway,
            $minute->format('Y-m-d'),
            $minute->format('H'),
            $minute->format('i')
        );
    }

    public static function keyTtlSeconds(): int
    {
        return (int) config('gateway_metrics.key_ttl_seconds', 7200);
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
     * Classify a gateway API response into a gateway error type.
     *
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
     * Classify middleware or early rejections from the HTTP response.
     *
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
                'error_type' => self::INFRASTRUCTURE_ERROR_CONNECTION_TIMEOUT,
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
            'payin/checkout',
            'v1/payment-checkout'
        );
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
