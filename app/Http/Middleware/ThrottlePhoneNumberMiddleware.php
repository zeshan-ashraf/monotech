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

        if ($this->isExcludedClientEmail($request)) {
            $logger->info('Payin phone throttle skipped (excluded client_email)', [
                'client_email' => $request->input('client_email'),
            ]);

            return $next($request);
        }

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

    /**
     * Clients listed in config('throttle_phone.excluded_emails') bypass phone cooldown.
     * Match is case-insensitive on request input `client_email`.
     */
    private function isExcludedClientEmail(Request $request): bool
    {
        $raw = $request->input('client_email');
        if (! is_string($raw) || $raw === '') {
            return false;
        }

        $email = strtolower(trim($raw));
        $excluded = array_values(array_filter(array_map(
            static fn ($e): string => strtolower(trim((string) $e)),
            config('throttle_phone.excluded_emails', [])
        )));

        return $excluded !== [] && in_array($email, $excluded, true);
    }
}
