<?php

namespace App\Services\Payin;

use Illuminate\Support\Facades\Log;

class PayinEasypaisaDiagnosticsLogger
{
    /**
     * @return array{payload: array<string, mixed>, level: string}
     */
    public function log(
        string $requestId,
        ?string $transactionRef,
        array $networkMetrics,
        ?string $responseCode = null,
        ?string $responseDesc = null,
        string $outcome = 'success',
        ?string $clientOrderId = null
    ): array {
        $payload = $this->buildPayload(
            $requestId,
            $transactionRef,
            $networkMetrics,
            $responseCode,
            $responseDesc,
            $clientOrderId
        );

        $level = $this->resolveLevel($outcome, $responseCode);

        Log::channel('payin_diagnostics')->log(
            $level,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return [
            'payload' => $payload,
            'level' => $level,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $requestId,
        ?string $transactionRef,
        array $networkMetrics,
        ?string $responseCode,
        ?string $responseDesc,
        ?string $clientOrderId
    ): array {
        return [
            'request_id' => $requestId,
            'client_order_id' => $clientOrderId,
            'transaction_ref' => $transactionRef,
            'server_hostname' => gethostname() ?: php_uname('n'),
            'public_ip' => ServerPublicIpResolver::resolve(),
            'resolved_ip' => $networkMetrics['resolved_ip'] ?? null,
            'connect_time' => $networkMetrics['connect_time'] ?? null,
            'ssl_time' => $networkMetrics['ssl_time'] ?? null,
            'ttfb' => $networkMetrics['ttfb'] ?? null,
            'total_time' => $networkMetrics['total_time'] ?? null,
            'http_code' => $networkMetrics['http_code'] ?? null,
            'response_code' => $responseCode,
            'response_desc' => $responseDesc,
        ];
    }

    private function resolveLevel(string $outcome, ?string $responseCode): string
    {
        if ($outcome === 'exception') {
            return 'error';
        }

        if ($outcome === 'timeout') {
            return 'warning';
        }

        if ($responseCode === '0000') {
            return 'info';
        }

        return 'warning';
    }
}
