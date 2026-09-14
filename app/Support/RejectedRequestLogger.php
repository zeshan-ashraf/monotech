<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class RejectedRequestLogger
{
    public const REQUEST_ATTR_LOGGED = 'rejected_request_logged';

    public static function log(
        Request $request,
        int $statusCode,
        string $reason,
        mixed $responseBody = null,
        array $extra = []
    ): void {
        Log::channel('rejected_requests')->warning('Request rejected', array_merge([
            'reason' => $reason,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'payment_method' => $request->input('payment_method'),
            'order_id' => $request->input('orderId'),
            'client_email' => $request->input('client_email'),
            'status_code' => $statusCode,
            'user_agent' => $request->header('User-Agent'),
            'content_type' => $request->header('Content-Type'),
            'request_body' => $request->getContent(),
            'request_parameters' => $request->all(),
            'request_headers' => $request->headers->all(),
            'response_body' => $responseBody,
            'timestamp' => now()->toDateTimeString(),
            'request_id' => uniqid('rejected_'),
        ], $extra));

        $request->attributes->set(self::REQUEST_ATTR_LOGGED, true);
    }

    public static function logResponse(Request $request, Response $response, string $reason): void
    {
        self::log(
            $request,
            $response->getStatusCode(),
            $reason,
            $response->getContent()
        );
    }
}
