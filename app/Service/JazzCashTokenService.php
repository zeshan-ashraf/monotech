<?php

namespace App\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JazzCashTokenService
{
    private const TOKEN_CACHE_TTL = 2700; // 45 minutes
    private const TOKEN_RETRY_ATTEMPTS = 2;
    private const TOKEN_RETRY_DELAY = 500;
    private const CACHE_PREFIX = 'jazzcash_token:';

    private $requestId;
    private $logChannel;

    public function __construct($requestId = null, $logChannel = 'payout_checkout')
    {
        $this->requestId = $requestId ?? Str::uuid()->toString();
        $this->logChannel = $logChannel;
    }

    public function getToken($clientId)
    {
        $startTime = microtime(true);
        $this->log('info', 'JazzCash token request initiated', [
            'client_id' => $clientId,
            'request_id' => $this->requestId
        ]);

        try {
            // Try to get existing token
            $token = $this->getClientToken($clientId);
            if ($token) {
                return $token;
            }

            // If no valid token, fetch new one
            return $this->fetchNewToken($clientId, $startTime);
        } catch (\Exception $e) {
            $this->log('error', 'Token retrieval failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processing_time' => microtime(true) - $startTime
            ]);
            throw $e;
        }
    }

    private function getClientToken($clientId)
    {
        $cacheKey = self::CACHE_PREFIX . $clientId;
        $tokenData = Cache::get($cacheKey);
        
        if ($tokenData) {
            $data = json_decode($tokenData, true);
            if (isset($data['expires_at']) && $data['expires_at'] > time()) {
                $this->log('info', 'Using cached token', [
                    'client_id' => $clientId,
                    'expires_in' => $data['expires_at'] - time(),
                    'cache_key' => $cacheKey
                ]);
                return $data['token'];
            } else {
                $this->log('info', 'Cached token expired', [
                    'client_id' => $clientId,
                    'expired_at' => $data['expires_at'] ?? 'unknown'
                ]);
            }
        }
        
        return null;
    }

    private function fetchNewToken($clientId, $startTime)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::TOKEN_RETRY_ATTEMPTS) {
            try {
                $apiStartTime = microtime(true);
                $token = $this->makeTokenRequest($clientId);
                
                $this->log('info', 'API token request successful', [
                    'client_id' => $clientId,
                    'attempt' => $attempt + 1,
                    'api_time' => microtime(true) - $apiStartTime
                ]);

                $this->storeClientToken($clientId, $token);
                
                $this->log('info', 'New token stored and ready', [
                    'client_id' => $clientId,
                    'total_time' => microtime(true) - $startTime
                ]);

                return $token;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                $this->log('warning', 'Token fetch attempt failed', [
                    'client_id' => $clientId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'will_retry' => $attempt < self::TOKEN_RETRY_ATTEMPTS
                ]);

                if ($attempt < self::TOKEN_RETRY_ATTEMPTS) {
                    usleep(self::TOKEN_RETRY_DELAY * 1000);
                }
            }
        }

        $this->log('error', 'All token fetch attempts failed', [
            'client_id' => $clientId,
            'total_attempts' => self::TOKEN_RETRY_ATTEMPTS,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        throw $lastException;
    }

    private function makeTokenRequest($clientId)
    {
        $url = env('JAZZCASH_GET_TOKEN_URL');
        $token = 'Basic ' . env('JAZZCASH_TOKEN');

        $this->log('debug', 'Preparing token request', [
            'client_id' => $clientId,
            'url' => $url
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                "Authorization: $token",
                "X-Client-ID: {$clientId}"
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $requestStart = microtime(true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        $this->log('debug', 'Token request completed', [
            'client_id' => $clientId,
            'http_code' => $httpCode,
            'total_time' => $info['total_time'],
            'connect_time' => $info['connect_time'],
            'request_time' => microtime(true) - $requestStart
        ]);

        curl_close($ch);

        if ($error) {
            $this->log('error', 'CURL error in token request', [
                'client_id' => $clientId,
                'error' => $error,
                'curl_info' => $info
            ]);
            throw new \Exception("CURL Error: $error");
        }

        if ($httpCode !== 200) {
            $this->log('error', 'Non-200 response from token request', [
                'client_id' => $clientId,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \Exception("HTTP Error: $httpCode");
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            $this->log('error', 'Invalid token response format', [
                'client_id' => $clientId,
                'response' => $response
            ]);
            throw new \Exception('Invalid token response format');
        }

        return $data['access_token'];
    }

    private function storeClientToken($clientId, $token)
    {
        $cacheKey = self::CACHE_PREFIX . $clientId;
        $expiresAt = time() + self::TOKEN_CACHE_TTL;
        
        $tokenData = json_encode([
            'token' => $token,
            'expires_at' => $expiresAt,
            'client_id' => $clientId,
            'created_at' => time()
        ]);

        Cache::put($cacheKey, $tokenData, self::TOKEN_CACHE_TTL);

        $this->log('info', 'Token stored in cache', [
            'client_id' => $clientId,
            'cache_key' => $cacheKey,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt)
        ]);
    }

    /**
     * Force refresh the token for a client
     * This is used when we get a 401 response indicating token expiration
     *
     * @param string $clientId
     * @return string
     * @throws \Exception
     */
    public function forceRefreshToken($clientId)
    {
        $this->log('info', 'Forcing token refresh', [
            'client_id' => $clientId,
            'reason' => 'Token expiration detected'
        ]);

        // Clear existing token from cache
        $cacheKey = self::CACHE_PREFIX . $clientId;
        Cache::forget($cacheKey);

        // Fetch new token
        $startTime = microtime(true);
        $token = $this->makeTokenRequest($clientId);
        
        // Store new token
        $this->storeClientToken($clientId, $token);
        
        $this->log('info', 'Token force refreshed successfully', [
            'client_id' => $clientId,
            'processing_time' => microtime(true) - $startTime
        ]);

        return $token;
    }

    private function log($level, $message, $context = [])
    {
        $context['request_id'] = $this->requestId;
        Log::channel($this->logChannel)->$level($message, $context);
    }
} 