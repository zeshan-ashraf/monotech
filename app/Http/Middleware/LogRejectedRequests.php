<?php

namespace App\Http\Middleware;

use App\Helpers\GatewayMetricHelper;
use App\Services\Dashboard\GatewayMetricService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRejectedRequests
{
    public function __construct(
        private readonly GatewayMetricService $gatewayMetrics
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

            Log::channel('rejected_requests')->warning('Request rejected', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->header('User-Agent'),
                'content_type' => $request->header('Content-Type'),
                'request_body' => $request->getContent(),
                'request_parameters' => $request->all(),
                'request_headers' => $request->headers->all(),
                'response_body' => $response->getContent(),
                'timestamp' => now()->toDateTimeString(),
                'request_id' => uniqid('rejected_'),
            ]);
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

        $gateway = (string) $request->input('payment_method', '');

        if (! GatewayMetricHelper::isSupportedGateway($gateway)) {
            return;
        }

        $startTime = $request->attributes->get(GatewayMetricHelper::REQUEST_ATTR_START_TIME);

        if (is_float($startTime) || is_int($startTime)) {
            $durationMs = (int) round((microtime(true) - (float) $startTime) * 1000);
            $this->gatewayMetrics->recordResponseTime($gateway, $durationMs);
        }

        $classification = GatewayMetricHelper::classifyMiddlewareRejection($request, $response);

        if ($classification !== null
            && $classification['category'] === GatewayMetricHelper::CATEGORY_APPLICATION
        ) {
            $this->gatewayMetrics->recordApplicationError($gateway, $classification['error_type']);
        }

        $this->gatewayMetrics->recordRejected($gateway);
        $request->attributes->set(GatewayMetricHelper::REQUEST_ATTR_OUTCOME_RECORDED, true);
    }
}
