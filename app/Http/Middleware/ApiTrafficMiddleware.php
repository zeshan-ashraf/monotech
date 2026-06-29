<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Helpers\ApiTrafficHelper;
use App\Services\Dashboard\ApiTrafficService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classifies API traffic, records incoming requests, and captures request timing.
 *
 * Must run before payment throttling, validation, and authentication middleware.
 */
class ApiTrafficMiddleware
{
    public function __construct(
        private readonly ApiTrafficService $apiTraffic
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $apiType = ApiTrafficHelper::classifyRequest($request);

        $request->attributes->set(ApiTrafficHelper::REQUEST_ATTR_API_TYPE, $apiType);
        $request->attributes->set(ApiTrafficHelper::REQUEST_ATTR_START_TIME, microtime(true));

        $this->apiTraffic->recordIncoming($apiType);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($request->attributes->get(ApiTrafficHelper::REQUEST_ATTR_OUTCOME_RECORDED)) {
            return;
        }

        $apiType = ApiTrafficHelper::resolveApiTypeFromRequest($request);
        $startTime = $request->attributes->get(ApiTrafficHelper::REQUEST_ATTR_START_TIME);

        if (is_float($startTime) || is_int($startTime)) {
            $durationMs = (int) round((microtime(true) - (float) $startTime) * 1000);
            $this->apiTraffic->recordResponseTime($apiType, $durationMs);
        }

        if ($response->getStatusCode() >= 400) {
            $this->apiTraffic->recordRejected($apiType);
        }
    }
}
