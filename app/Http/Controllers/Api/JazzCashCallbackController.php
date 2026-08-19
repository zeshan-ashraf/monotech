<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JazzCashCallbackController extends Controller
{
    public function handleCallback(Request $request)
    {
        // Log the complete response to a dedicated channel
        Log::channel('jazzcash_payin_callback')->info('JazzCash Callback Received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Return a success response to JazzCash
        return response()->json([
            'status' => 'success',
            'message' => 'Callback received successfully'
        ]);
    }
} 