<?php

namespace App\Http\Middleware;

use App\Support\PayinRestrictionExclusion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global payin checkout rate limit — shared across all clients and IPs.
 */
class ThrottlePayinCheckoutGlobalMiddleware
{
    private const LIMITER_KEY = 'payin:checkout:global';

    public function handle(Request $request, Closure $next): Response
    {
        if (PayinRestrictionExclusion::isExcludedPhone($request->input('phone'))) {
            return $next($request);
        }

        $maxAttempts = (int) config('throttle_phone.global_checkout_per_minute', 40);
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts(self::LIMITER_KEY, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn(self::LIMITER_KEY);

            return response()->json([
                'status' => 'error',
                'message' => 'Too many payin requests. Please try again later.',
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit(self::LIMITER_KEY, $decaySeconds);

        return $next($request);
    }
}
