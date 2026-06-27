<?php

namespace App\Http\Middleware;

use App\Helpers\GatewayMetricHelper;
use App\Services\Dashboard\GatewayMetricService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records gateway request counts at the start of payin checkout routes.
 */
class RecordGatewayRequestMetricsMiddleware
{
    public function __construct(
        private readonly GatewayMetricService $gatewayMetrics
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (GatewayMetricHelper::isPayinCheckoutRequest($request)) {
            $gateway = (string) $request->input('payment_method', '');

            if (GatewayMetricHelper::isSupportedGateway($gateway)) {
                $request->attributes->set(
                    GatewayMetricHelper::REQUEST_ATTR_START_TIME,
                    microtime(true)
                );
                $this->gatewayMetrics->recordRequest($gateway);
            }
        }

        return $next($request);
    }
}
