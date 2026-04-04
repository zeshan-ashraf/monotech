<?php

namespace App\Http\Middleware;

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
        $phone = $request->input('phone');
        if (!$phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is required.'
            ], 400);
        }

        $cacheKey = 'payin:phone:lock:' . $phone;
        if (Cache::has($cacheKey)) {
            $seconds = Cache::get($cacheKey) - time();
            $wait = $seconds > 0 ? $seconds : 180;

            $logger->info('Payin phone throttle: cooldown active', [
                'phone' => $phone,
                'retry_after_seconds' => $wait,
            ]);

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
} 