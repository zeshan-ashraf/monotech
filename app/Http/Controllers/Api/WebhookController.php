<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $query = $request->query();
        $callbackUrl = $query['url'] ?? $request->input('url');

        if (is_string($callbackUrl) && $callbackUrl !== '') {
            $query['data'] = $this->fetchCallbackData($callbackUrl);
        }

        Log::channel('zee')->info('Webhook received', [
            'method' => $request->method(),
            'query' => $query,
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received',
        ]);
    }

    private function fetchCallbackData(string $url): array
    {
        if (! $this->isAllowedCallbackUrl($url)) {
            return ['error' => 'Callback URL host is not allowed'];
        }

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return [
                    'error' => 'Callback URL request failed',
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                ];
            }

            $json = $response->json();

            return is_array($json)
                ? $json
                : ['raw' => $response->body()];
        } catch (ConnectionException $e) {
            return ['error' => 'Connection failed: '.$e->getMessage()];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function isAllowedCallbackUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'easypay.easypaisa.com.pk';
    }
}
