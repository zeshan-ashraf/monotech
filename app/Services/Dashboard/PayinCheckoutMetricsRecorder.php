<?php

namespace App\Services\Dashboard;

use App\Helpers\GatewayMetricHelper;
use Illuminate\Http\Request;

/**
 * Shared payin checkout metrics recording for all checkout controllers.
 */
class PayinCheckoutMetricsRecorder
{
    public function __construct(
        private readonly GatewayMetricService $gatewayMetrics
    ) {
    }

    public function recordValidatedRequest(Request $request, string $gateway): void
    {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        if ($request->attributes->get('gateway_metrics_request_recorded')) {
            return;
        }

        if (! $request->attributes->has(GatewayMetricHelper::REQUEST_ATTR_START_TIME)) {
            $request->attributes->set(
                GatewayMetricHelper::REQUEST_ATTR_START_TIME,
                microtime(true)
            );
        }

        $this->gatewayMetrics->recordRequest($gateway);
        $request->attributes->set('gateway_metrics_request_recorded', true);
    }

    public function recordGatewayCheckoutSuccess(Request $request, string $gateway, float $startTime): void
    {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordSuccess($gateway);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordGatewayCheckoutPending(Request $request, string $gateway, float $startTime): void
    {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordPending($gateway);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordApplicationCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $applicationErrorType
    ): void {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordApplicationError($gateway, $applicationErrorType);
        $this->gatewayMetrics->recordRejected($gateway);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordInfrastructureCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $infrastructureErrorType
    ): void {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordInfrastructureError($gateway, $infrastructureErrorType);
        $this->gatewayMetrics->recordFailed($gateway);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordGatewayCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        ?string $responseCode,
        ?string $responseDescription
    ): void {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordGatewayResponseFailure($gateway, $responseCode, $responseDescription);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordClassifiedCheckoutFailure(
        Request $request,
        string $gateway,
        float $startTime,
        string $category,
        string $errorType
    ): void {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        match ($category) {
            GatewayMetricHelper::CATEGORY_INFRASTRUCTURE => $this->gatewayMetrics->recordInfrastructureError($gateway, $errorType),
            GatewayMetricHelper::CATEGORY_GATEWAY => $this->gatewayMetrics->recordGatewayError($gateway, $errorType),
            GatewayMetricHelper::CATEGORY_APPLICATION => $this->gatewayMetrics->recordApplicationError($gateway, $errorType),
            default => null,
        };

        if ($category === GatewayMetricHelper::CATEGORY_APPLICATION) {
            $this->gatewayMetrics->recordRejected($gateway);
        } else {
            $this->gatewayMetrics->recordFailed($gateway);
        }

        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }

    public function recordTimeoutFailure(Request $request, string $gateway, float $startTime): void
    {
        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $this->gatewayMetrics->recordTimeout($gateway);
        $this->gatewayMetrics->recordFailed($gateway);
        $this->gatewayMetrics->finalizeCheckoutMetrics($request, $gateway, $startTime);
    }
}
