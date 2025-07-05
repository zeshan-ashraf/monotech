<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Log rate limit exceptions
        $this->reportable(function (ThrottleRequestsException $e, Request $request) {
            Log::channel('rejected_requests')->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'full_url' => $request->fullUrl(),
                'user_agent' => $request->header('User-Agent'),
                'content_type' => $request->header('Content-Type'),
                'request_body' => $request->getContent(),
                'request_parameters' => $request->all(),
                'request_headers' => $request->headers->all(),
                'exception_message' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
                'request_id' => uniqid('rate_limited_')
            ]);
        });
    }
}
