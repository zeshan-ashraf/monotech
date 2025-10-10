<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckWhitelistedIPs
{
    /**
     * List of allowed IPs
     * 
     * @var array
     */
    protected $whitelistedIPs = [
        '202.79.174.166',
        '202.79.174.177',
        '143.92.56.160',
        '139.5.202.78',
        '51.15.127.156',
        '148.66.59.115',
        '60.251.151.239',
        '60.251.151.229',
        '122.146.88.8',
        '220.128.103.10',
        '122.146.89.64',
        '60.251.156.172',
        '218.211.60.8',
        '47.236.184.218',
        '47.243.20.101',
        '47.243.28.60',
        '47.243.27.245',
        '47.243.22.1',
        '47.242.70.99',
        '47.243.52.109',
        '8.210.148.205',
        '47.76.108.79',
        '47.91.115.10',
        '47.91.109.236',
        '47.76.114.178',
        '47.76.129.253',
        '47.239.207.212',
        '47.76.136.25',
        '47.86.47.153',
        '182.239.92.55',
        '47.237.104.135',
        '47.236.253.59',
        '47.236.184.75',
        '182.239.115.38',
        '51.17.160.132',
        '154.205.145.213',
        '122.116.231.64',
        '4.247.181.1',
        '4.191.73.136',
        '113.15.142.93',
        '116.30.13.152',
        '223.104.77.202',
        '47.239.84.216',
        '47.243.21.81',
        '47.239.88.177',
        '180.190.114.230',
        '43.217.190.250',
        '47.239.73.139',
        '8.218.10.84',
        '8.210.44.164',
        '47.239.220.241',
        '47.239.88.177',
        '47.243.21.81',
        '47.239.84.216',
        '47.76.190.72',
        '47.76.46.135',
        '47.86.44.52',
        '47.86.45.213',
        '47.238.232.159',
        '47.86.39.19',
        '27.124.21.158',
        '47.86.16.76',
        '47.86.26.33',
        '47.86.21.18',
        '47.86.47.142',
        '27.124.46.151',
        '143.92.58.147',
        '51.17.160.132',
        '154.205.145.213',
        '3.115.201.98',
        '43.207.180.114',
        '43.207.38.222',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request is from Postman
        $userAgent = $request->header('User-Agent');
        $isPostman = str_contains($userAgent, 'PostmanRuntime');

        // Only check testing key if request is from Postman
        if ($isPostman) {
            // $testingKey = $request->input('testing_key') ?? $request->header('X-Testing-Key');
            $testingKey = $request->header('X-Testing-Key');
            $envTestingKey = env('TESTING_SECRET_KEY');

            if ($testingKey && $envTestingKey && $testingKey === $envTestingKey) {
                Log::channel('payout')->info('Postman testing key authentication successful', [
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user_agent' => $userAgent,
                    'testing_key_provided' => !empty($testingKey)
                ]);
                return $next($request);
            } else {
                Log::channel('payout')->warning('Postman request with invalid or missing testing key', [
                    'ip' => $request->ip(),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user_agent' => $userAgent,
                    'testing_key_provided' => !empty($testingKey)
                ]);
                return response()->json(['error' => 'Invalid or missing testing key for Postman request'], 403);
            }
        }

        // Log the incoming request
        Log::channel('payout')->info('Incoming request to whitelisted endpoint', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'user_agent' => $userAgent,
            'request_time' => now()->toDateTimeString()
        ]);

        if (!in_array($request->ip(), $this->whitelistedIPs)) {
            Log::channel('payout')->warning('Unauthorized IP attempt', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_agent' => $userAgent
            ]);
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        return $next($request);
    }
}
