<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClientSiteHealthService
{
    public const TIMEOUT_SECONDS = 15;

    /**
     * Live-check a client site URL from the server.
     *
     * @return array{
     *     working: bool,
     *     http_status: int|null,
     *     latency_ms: int|null,
     *     final_url: string|null,
     *     ssl_ok: bool|null,
     *     dns_ok: bool,
     *     error: string|null,
     *     checked_url: string
     * }
     */
    public function check(string $url): array
    {
        $url = $this->normalizeUrl($url);

        $result = [
            'working' => false,
            'http_status' => null,
            'latency_ms' => null,
            'final_url' => null,
            'ssl_ok' => null,
            'dns_ok' => false,
            'error' => null,
            'checked_url' => $url,
        ];

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $result['error'] = 'Invalid URL';

            return $result;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! $host) {
            $result['error'] = 'Invalid URL host';

            return $result;
        }

        $ip = @gethostbyname($host);
        $result['dns_ok'] = is_string($ip)
            && $ip !== $host
            && (bool) filter_var($ip, FILTER_VALIDATE_IP);

        if (! $result['dns_ok']) {
            $result['error'] = 'DNS resolution failed';

            return $result;
        }

        if ($scheme === 'https') {
            $result['ssl_ok'] = $this->checkSsl($host);
        }

        $start = microtime(true);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => false,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true,
                    ],
                    'verify' => true,
                    'http_errors' => false,
                ])
                ->withHeaders([
                    'User-Agent' => 'MonotechSiteHealthCheck/1.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            $result['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
            $result['http_status'] = $response->status();
            $result['final_url'] = (string) $response->effectiveUri();
            $result['working'] = true;

            if ($scheme === 'https') {
                $result['ssl_ok'] = true;
            }
        } catch (ConnectionException $e) {
            $result['latency_ms'] = (int) round((microtime(true) - $start) * 1000);

            if ($scheme === 'https') {
                try {
                    $retryStart = microtime(true);
                    $response = Http::timeout(self::TIMEOUT_SECONDS)
                        ->withOptions([
                            'allow_redirects' => true,
                            'verify' => false,
                            'http_errors' => false,
                        ])
                        ->withHeaders([
                            'User-Agent' => 'MonotechSiteHealthCheck/1.0',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                        ])
                        ->get($url);

                    $result['latency_ms'] = (int) round((microtime(true) - $retryStart) * 1000);
                    $result['http_status'] = $response->status();
                    $result['final_url'] = (string) $response->effectiveUri();
                    $result['ssl_ok'] = false;
                    $result['working'] = true;
                    $result['error'] = 'SSL certificate problem: '.$e->getMessage();

                    return $result;
                } catch (Throwable) {
                    // Fall through to original connection error.
                }
            }

            $result['error'] = $e->getMessage();
            $result['working'] = false;
        } catch (Throwable $e) {
            $result['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
            $result['error'] = $e->getMessage();
            $result['working'] = false;
        }

        return $result;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    private function checkSsl(string $host): bool
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $client = @stream_socket_client(
            'ssl://'.$host.':443',
            $errno,
            $errstr,
            self::TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $client) {
            return false;
        }

        fclose($client);

        return true;
    }
}
