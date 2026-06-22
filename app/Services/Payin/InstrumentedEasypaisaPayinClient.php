<?php

namespace App\Services\Payin;

use GuzzleHttp\TransferStats;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Zfhassaan\Easypaisa\Easypaisa;

class InstrumentedEasypaisaPayinClient
{
    private const HTTP_TIMEOUT_SECONDS = 60;

    public function __construct(
        private readonly PayinEasypaisaDiagnosticsLogger $diagnosticsLogger
    ) {
    }

    /**
     * Mirrors Zfhassaan\Easypaisa\Easypaisa::sendRequest() with network timing capture.
     *
     * @return array{response: mixed, diagnostics: array<string, mixed>, diagnostics_level: string}
     */
    public function sendRequest(
        array $request,
        string $requestId,
        ?string $transactionRef = null,
        ?string $clientOrderId = null
    ): array {
        $emptyMetrics = $this->emptyNetworkMetrics();

        try {
            $email = $request['emailAddress'] ?? null;

            if (
                intval($request['transactionAmount'] ?? 0) < 0
                || empty($request['orderId'])
                || empty($request['mobileAccountNo'])
            ) {
                return $this->wrapResponse(
                    response()->json(
                        ['status' => false, 'message' => 'Missing Arguments.'],
                        Response::HTTP_NOT_ACCEPTABLE
                    ),
                    $this->diagnosticsLogger->log(
                        $requestId,
                        $transactionRef ?? ($request['orderId'] ?? null),
                        $emptyMetrics,
                        null,
                        'Missing Arguments.',
                        'failure',
                        $clientOrderId
                    )
                );
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->wrapResponse(
                    response()->json(
                        ['status' => false, 'message' => 'Email format is incorrect'],
                        Response::HTTP_CONFLICT
                    ),
                    $this->diagnosticsLogger->log(
                        $requestId,
                        $transactionRef ?? ($request['orderId'] ?? null),
                        $emptyMetrics,
                        null,
                        'Email format is incorrect',
                        'failure',
                        $clientOrderId
                    )
                );
            }

            $easypaisa = new Easypaisa();
            $credentials = $this->invokeProtected($easypaisa, 'getCredentials');
            $apiUrl = $this->invokeProtected($easypaisa, 'getApiUrl');
            $storeId = $this->invokeProtected($easypaisa, 'getStoreId');

            $data = [
                'orderId' => strip_tags($request['orderId']),
                'storeId' => $storeId,
                'transactionAmount' => strip_tags($request['transactionAmount']),
                'transactionType' => 'MA',
                'mobileAccountNo' => strip_tags($request['mobileAccountNo']),
                'emailAddress' => strip_tags($request['emailAddress']),
            ];

            $transferStats = null;

            try {
                $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                    ->withOptions([
                        'on_stats' => static function (TransferStats $stats) use (&$transferStats): void {
                            $transferStats = $stats;
                        },
                    ])
                    ->withHeaders([
                        'credentials' => $credentials,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($apiUrl, $data);
            } catch (ConnectionException $e) {
                return $this->wrapResponse(
                    response()->json(
                        ['status' => false, 'message' => $e->getMessage()],
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    ),
                    $this->diagnosticsLogger->log(
                        $requestId,
                        $transactionRef ?? $data['orderId'],
                        $emptyMetrics,
                        null,
                        $e->getMessage(),
                        'timeout',
                        $clientOrderId
                    )
                );
            }

            $networkMetrics = $this->extractNetworkMetrics($transferStats);
            $result = $response->json();
            $responseCode = is_array($result) ? ($result['responseCode'] ?? null) : null;
            $responseDesc = is_array($result) ? ($result['responseDesc'] ?? null) : null;
            $outcome = $responseCode === '0000' ? 'success' : 'failure';

            if ($networkMetrics['http_code'] === null) {
                $networkMetrics['http_code'] = $response->status();
            }

            return $this->wrapResponse(
                $result,
                $this->diagnosticsLogger->log(
                    $requestId,
                    $transactionRef ?? $data['orderId'],
                    $networkMetrics,
                    $responseCode,
                    $responseDesc,
                    $outcome,
                    $clientOrderId
                )
            );
        } catch (\Exception $e) {
            return $this->wrapResponse(
                response()->json(
                    ['status' => false, 'message' => $e->getMessage()],
                    Response::HTTP_INTERNAL_SERVER_ERROR
                ),
                $this->diagnosticsLogger->log(
                    $requestId,
                    $transactionRef ?? ($request['orderId'] ?? null),
                    $emptyMetrics,
                    null,
                    $e->getMessage(),
                    'exception',
                    $clientOrderId
                )
            );
        }
    }

    /**
     * @param array{payload: array<string, mixed>, level: string} $diagnosticsMeta
     * @return array{response: mixed, diagnostics: array<string, mixed>, diagnostics_level: string}
     */
    private function wrapResponse(mixed $response, array $diagnosticsMeta): array
    {
        return [
            'response' => $response,
            'diagnostics' => $diagnosticsMeta['payload'],
            'diagnostics_level' => $diagnosticsMeta['level'],
        ];
    }

    private function invokeProtected(Easypaisa $easypaisa, string $method): mixed
    {
        $reflection = new \ReflectionMethod($easypaisa, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($easypaisa);
    }

    private function extractNetworkMetrics(?TransferStats $stats): array
    {
        if ($stats === null) {
            return $this->emptyNetworkMetrics();
        }

        $handlerStats = $stats->getHandlerStats();
        $connectTime = $handlerStats['connect_time'] ?? null;
        $appConnectTime = $handlerStats['appconnect_time'] ?? 0;

        return [
            'resolved_ip' => $handlerStats['primary_ip'] ?? null,
            'connect_time' => $connectTime,
            'ssl_time' => $connectTime !== null
                ? max(0, $appConnectTime - $connectTime)
                : null,
            'ttfb' => $handlerStats['starttransfer_time'] ?? null,
            'total_time' => $handlerStats['total_time'] ?? null,
            'http_code' => $handlerStats['http_code'] ?? null,
        ];
    }

    private function emptyNetworkMetrics(): array
    {
        return [
            'resolved_ip' => null,
            'connect_time' => null,
            'ssl_time' => null,
            'ttfb' => null,
            'total_time' => null,
            'http_code' => null,
        ];
    }
}
