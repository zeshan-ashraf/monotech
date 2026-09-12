<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApiTrafficHelper;
use App\Helpers\GatewayMetricHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records API traffic metrics at payment-flow integration points.
 */
class ApiTrafficMetricsRecorder
{
    public function __construct(
        private readonly ApiTrafficService $apiTraffic
    ) {
    }

    public function recordAccepted(Request $request): void
    {
        $this->apiTraffic->recordAccepted($this->apiType($request));
    }

    public function recordRejected(Request $request): void
    {
        $this->apiTraffic->recordRejected($this->apiType($request));
    }

    public function recordGatewayCall(Request $request): void
    {
        $this->apiTraffic->recordGatewayCall($this->apiType($request));
    }

    public function recordGatewayCheckoutSuccess(Request $request, float $startTime): void
    {
        $apiType = $this->apiType($request);

        $this->apiTraffic->recordGatewayCall($apiType);
        $this->apiTraffic->recordCompleted($apiType);
        $this->apiTraffic->recordSuccess($apiType);
        $this->finalizeResponseTime($request, $startTime);
    }

    public function recordGatewayCheckoutPending(Request $request, float $startTime): void
    {
        $apiType = $this->apiType($request);

        $this->apiTraffic->recordGatewayCall($apiType);
        $this->apiTraffic->recordCompleted($apiType);
        $this->apiTraffic->recordPending($apiType);
        $this->finalizeResponseTime($request, $startTime);
    }

    public function recordPreGatewayRejection(
        Request $request,
        float $startTime,
        ?string $applicationErrorType = null
    ): void {
        $apiType = $this->apiType($request);

        if ($applicationErrorType !== null) {
            $this->apiTraffic->recordApplicationError($apiType, $applicationErrorType);
        }

        $this->apiTraffic->recordRejected($apiType);
        $this->finalizeResponseTime($request, $startTime);
        $this->markOutcomeRecorded($request);
    }

    public function recordApplicationCheckoutFailure(
        Request $request,
        float $startTime,
        string $applicationErrorType
    ): void {
        $this->recordPreGatewayRejection($request, $startTime, $applicationErrorType);
    }

    public function recordInfrastructureCheckoutFailure(
        Request $request,
        float $startTime,
        string $infrastructureErrorType
    ): void {
        $apiType = $this->apiType($request);

        $this->apiTraffic->recordInfrastructureError($apiType, $infrastructureErrorType);
        $this->apiTraffic->recordFailure($apiType);
        $this->apiTraffic->recordGatewayCall($apiType);
        $this->finalizeResponseTime($request, $startTime);
        $this->markOutcomeRecorded($request);
    }

    public function recordGatewayCheckoutFailure(
        Request $request,
        float $startTime,
        ?string $responseCode,
        ?string $responseDescription
    ): void {
        $apiType = $this->apiType($request);

        $this->apiTraffic->recordGatewayCall($apiType);
        $this->apiTraffic->recordFailure($apiType);
        $this->apiTraffic->recordCompleted($apiType);

        $classification = GatewayMetricHelper::classifyGatewayResponse(
            (string) $request->input('payment_method', ''),
            $responseCode,
            $responseDescription
        );

        if ($classification !== null) {
            $this->apiTraffic->recordGatewayError($apiType, $classification['error_type']);
        }

        $this->finalizeResponseTime($request, $startTime);
        $this->markOutcomeRecorded($request);
    }

    public function recordClassifiedCheckoutFailure(
        Request $request,
        float $startTime,
        string $category,
        string $errorType
    ): void {
        $apiType = $this->apiType($request);

        match ($category) {
            GatewayMetricHelper::CATEGORY_INFRASTRUCTURE => $this->apiTraffic->recordInfrastructureError($apiType, $errorType),
            GatewayMetricHelper::CATEGORY_GATEWAY => $this->apiTraffic->recordGatewayError($apiType, $errorType),
            GatewayMetricHelper::CATEGORY_APPLICATION => $this->apiTraffic->recordApplicationError($apiType, $errorType),
            default => null,
        };

        if ($category === GatewayMetricHelper::CATEGORY_APPLICATION) {
            $this->apiTraffic->recordRejected($apiType);
        } else {
            $this->apiTraffic->recordFailure($apiType);
            $this->apiTraffic->recordGatewayCall($apiType);
        }

        $this->finalizeResponseTime($request, $startTime);
        $this->markOutcomeRecorded($request);
    }

    public function recordTimeoutFailure(Request $request, float $startTime): void
    {
        $apiType = $this->apiType($request);

        $this->apiTraffic->recordTimeout($apiType);
        $this->apiTraffic->recordFailure($apiType);
        $this->apiTraffic->recordGatewayCall($apiType);
        $this->finalizeResponseTime($request, $startTime);
        $this->markOutcomeRecorded($request);
    }

    public function recordMiddlewareRejection(Request $request, Response $response): void
    {
        if ($request->attributes->get(ApiTrafficHelper::REQUEST_ATTR_OUTCOME_RECORDED)) {
            return;
        }

        $apiType = $this->apiType($request);
        $this->apiTraffic->recordRejected($apiType);

        $classification = GatewayMetricHelper::classifyMiddlewareRejection($request, $response);

        if ($classification !== null) {
            match ($classification['category']) {
                GatewayMetricHelper::CATEGORY_INFRASTRUCTURE => $this->apiTraffic->recordInfrastructureError(
                    $apiType,
                    $classification['error_type']
                ),
                GatewayMetricHelper::CATEGORY_GATEWAY => $this->apiTraffic->recordGatewayError(
                    $apiType,
                    $classification['error_type']
                ),
                GatewayMetricHelper::CATEGORY_APPLICATION => $this->apiTraffic->recordApplicationError(
                    $apiType,
                    $classification['error_type']
                ),
                default => null,
            };
        }

        $startTime = $request->attributes->get(ApiTrafficHelper::REQUEST_ATTR_START_TIME);

        if (is_float($startTime) || is_int($startTime)) {
            $durationMs = (int) round((microtime(true) - (float) $startTime) * 1000);
            $this->apiTraffic->recordResponseTime($apiType, $durationMs);
        }

        $this->markOutcomeRecorded($request);
    }

    private function finalizeResponseTime(Request $request, float $startTime): void
    {
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $this->apiTraffic->recordResponseTime($this->apiType($request), $durationMs);
        $this->markOutcomeRecorded($request);
    }

    private function apiType(Request $request): string
    {
        return ApiTrafficHelper::resolveApiTypeFromRequest($request);
    }

    private function markOutcomeRecorded(Request $request): void
    {
        $request->attributes->set(ApiTrafficHelper::REQUEST_ATTR_OUTCOME_RECORDED, true);
    }
}
