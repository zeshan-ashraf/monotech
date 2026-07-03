<?php

namespace App\Http\Middleware;

use App\Support\PayinRestrictionExclusion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class ThrottlePhoneNumberMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $logger = Log::channel('throttle_phone');
        $requestContext = $this->requestContext($request);

        if (PayinRestrictionExclusion::shouldBypass($request)) {
            $logger->info('Payin phone throttle skipped (excluded client)', array_merge($requestContext, [
                'reason' => 'excluded_client',
            ]));

            return $next($request);
        }

        $phone = $request->input('phone');
        if (!$phone) {
            $logger->info('Payin phone throttle: phone missing', $requestContext);

            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is required.'
            ], 400);
        }

        $cacheKey = 'payin:phone:lock:' . $phone;
        if (Cache::has($cacheKey)) {
            $seconds = Cache::get($cacheKey) - time();
            $wait = $seconds > 0 ? $seconds : 180;

            $logger->info('Payin phone throttle: cooldown active', array_merge($requestContext, [
                'retry_after_seconds' => $wait,
            ]));

            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests for this phone number. Please wait for the cooldown period before trying again.',
                //'retry_after_seconds' => $wait,
            ], 429);
        }

        // Store the phone in cache for 3 minutes (180 seconds)
        Cache::put($cacheKey, time() + 180, 180);

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestContext(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'order_id' => $request->input('orderId'),
            'client_email' => $request->input('client_email'),
            'phone' => $request->input('phone'),
            'payment_method' => $request->input('payment_method'),
            'amount' => $request->input('amount'),
            'callback_url' => $request->input('callback_url'),
        ];
    }
}
