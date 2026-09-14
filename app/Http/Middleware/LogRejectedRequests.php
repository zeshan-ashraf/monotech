<?php

namespace App\Http\Middleware;

use App\Helpers\GatewayMetricHelper;
use App\Services\Dashboard\ApiTrafficMetricsRecorder;
use App\Services\Dashboard\GatewayMetricService;
use App\Support\RejectedRequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRejectedRequests
{
    public function __construct(
        private readonly GatewayMetricService $gatewayMetrics,
        private readonly ApiTrafficMetricsRecorder $apiTrafficMetrics
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            $this->recordRejectedGatewayMetrics($request, $response);
            $this->apiTrafficMetrics->recordMiddlewareRejection($request, $response);

            if (! $request->attributes->get(RejectedRequestLogger::REQUEST_ATTR_LOGGED)) {
                RejectedRequestLogger::logResponse($request, $response, 'http_error');
            }
        }

        return $response;
    }

    /**
     * Pre-gateway rejection — request never reached Easypaisa/JazzCash API.
     */
    private function recordRejectedGatewayMetrics(Request $request, Response $response): void
    {
        if (! GatewayMetricHelper::isPayinCheckoutRequest($request)) {
            return;
        }

        if ($request->attributes->get(GatewayMetricHelper::REQUEST_ATTR_OUTCOME_RECORDED)) {
            return;
        }

        $gateway = GatewayMetricHelper::resolveCheckoutGateway($request);

        if ($gateway === null) {
            return;
        }

        $startTime = $request->attributes->get(GatewayMetricHelper::REQUEST_ATTR_START_TIME);

        if (is_float($startTime) || is_int($startTime)) {
            $durationMs = (int) round((microtime(true) - (float) $startTime) * 1000);
            $this->gatewayMetrics->recordResponseTime($gateway, $durationMs);
        }

        $this->gatewayMetrics->recordMiddlewareRejection($gateway, $request, $response);
        $request->attributes->set(GatewayMetricHelper::REQUEST_ATTR_OUTCOME_RECORDED, true);
    }
}
