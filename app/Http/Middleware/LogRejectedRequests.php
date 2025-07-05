<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRejectedRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log requests that result in error responses
        if ($response->getStatusCode() >= 400) {
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
                'request_id' => uniqid('rejected_')
            ]);
        }

        return $response;
    }
} 