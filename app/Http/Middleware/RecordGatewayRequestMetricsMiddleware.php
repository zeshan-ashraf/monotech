<?php

namespace App\Http\Middleware;

use App\Helpers\GatewayMetricHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures checkout timing at the start of payin checkout routes.
 */
class RecordGatewayRequestMetricsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (GatewayMetricHelper::isPayinCheckoutRequest($request)) {
            $request->attributes->set(
                GatewayMetricHelper::REQUEST_ATTR_START_TIME,
                microtime(true)
            );
        }

        return $next($request);
    }
}
