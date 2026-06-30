<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::channel('zee')->info('Webhook received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received',
        ]);
    }
}
