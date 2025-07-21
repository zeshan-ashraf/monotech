<?php

namespace App\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EasypaisaTimestampService
{
    private const TIMESTAMP_CACHE_TTL = 300; // 5 minutes
    private const TIMESTAMP_RETRY_ATTEMPTS = 2;
    private const TIMESTAMP_RETRY_DELAY = 500;
    private const CACHE_PREFIX = 'easypaisa_timestamp:';

    private $requestId;
    private $logChannel;

    public function __construct($requestId = null, $logChannel = 'payout_checkout')
    {
        $this->requestId = $requestId ?? Str::uuid()->toString();
        $this->logChannel = $logChannel;
    }

    public function getTimestamp($clientId, $clientSecret, $channel)
    {
        $startTime = microtime(true);
        $this->log('info', 'Easypaisa timestamp request initiated', [
            'client_id' => $clientId,
            'request_id' => $this->requestId
        ]);

        try {
            // Try to get existing timestamp
            $timestamp = $this->getCachedTimestamp($clientId);
            if ($timestamp) {
                return $timestamp;
            }

            // If no valid timestamp, fetch new one
            return $this->fetchNewTimestamp($clientId, $clientSecret, $channel, $startTime);
        } catch (\Exception $e) {
            $this->log('error', 'Timestamp retrieval failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processing_time' => microtime(true) - $startTime
            ]);
            throw $e;
        }
    }

    private function getCachedTimestamp($clientId)
    {
        $cacheKey = self::CACHE_PREFIX . $clientId;
        $timestampData = Cache::get($cacheKey);
        
        if ($timestampData) {
            $data = json_decode($timestampData, true);
            if (isset($data['expires_at']) && $data['expires_at'] > time()) {
                $this->log('info', 'Using cached timestamp', [
                    'client_id' => $clientId,
                    'expires_in' => $data['expires_at'] - time(),
                    'cache_key' => $cacheKey
                ]);
                return $data['timestamp'];
            } else {
                $this->log('info', 'Cached timestamp expired', [
                    'client_id' => $clientId,
                    'expired_at' => $data['expires_at'] ?? 'unknown'
                ]);
            }
        }
        
        return null;
    }

    private function fetchNewTimestamp($clientId, $clientSecret, $channel, $startTime)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::TIMESTAMP_RETRY_ATTEMPTS) {
            try {
                $apiStartTime = microtime(true);
                $timestamp = $this->makeTimestampRequest($clientId, $clientSecret, $channel);
                
                $this->log('info', 'API timestamp request successful', [
                    'client_id' => $clientId,
                    'attempt' => $attempt + 1,
                    'api_time' => microtime(true) - $apiStartTime
                ]);

                $this->storeTimestamp($clientId, $timestamp);
                
                $this->log('info', 'New timestamp stored and ready', [
                    'client_id' => $clientId,
                    'total_time' => microtime(true) - $startTime
                ]);

                return $timestamp;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                $this->log('warning', 'Timestamp fetch attempt failed', [
                    'client_id' => $clientId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'will_retry' => $attempt < self::TIMESTAMP_RETRY_ATTEMPTS
                ]);

                if ($attempt < self::TIMESTAMP_RETRY_ATTEMPTS) {
                    usleep(self::TIMESTAMP_RETRY_DELAY * 1000);
                }
            }
        }

        $this->log('error', 'All timestamp fetch attempts failed', [
            'client_id' => $clientId,
            'total_attempts' => self::TIMESTAMP_RETRY_ATTEMPTS,
            'final_error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        throw $lastException;
    }

    private function makeTimestampRequest($clientId, $clientSecret, $channel)
    {
        $url = env('EASYPAY_LOGIN_URL');
        
        $this->log('debug', 'Preparing timestamp request', [
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
            CURLOPT_POSTFIELDS => '{
                "LoginPayload":"rUpnSSD2Vp4VLWWH3dHXpTml7ntgvBFkh1EOhWgGRg2XCir4nzA++dwPb8aMeeQjeH90qMpH49NMz34yh6qMZ8d+SGU5AMMea08cWiPgqlDe02i+mZSc0Uh7YN7D5Mdo1YtMMEqx8WOA5z5VOFZ4W0dII2YxpdZ+vz1p4kqxFpE4671U+qSp6loxw02v/hDuYlI8hf4pFi/scvz577kwSeT3S7DxywFaB6mp3Sgi6yUjrXdj+Tmilhe9mNRNP65t0LiHq92y268lwJTetE0ZuYas1cyGeRln1YaUeyby/U0IIm5BlqGVfuxTvWDvGLPk81oyv0Q7TY135MW7ADuNbw=="
            }',
            CURLOPT_HTTPHEADER => [
                "X-IBM-Client-Id: $clientId",
                "X-IBM-Client-Secret: $clientSecret",
                "X-Channel: $channel",
                'Content-Type: application/json',
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
        
        $this->log('debug', 'Timestamp request completed', [
            'client_id' => $clientId,
            'http_code' => $httpCode,
            'total_time' => $info['total_time'],
            'connect_time' => $info['connect_time'],
            'request_time' => microtime(true) - $requestStart
        ]);

        curl_close($ch);

        if ($error) {
            $this->log('error', 'CURL error in timestamp request', [
                'client_id' => $clientId,
                'error' => $error,
                'curl_info' => $info
            ]);
            throw new \Exception("CURL Error: $error");
        }

        if ($httpCode !== 200) {
            $this->log('error', 'Non-200 response from timestamp request', [
                'client_id' => $clientId,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \Exception("HTTP Error: $httpCode");
        }

        $data = json_decode($response, true);
        if (!isset($data['Timestamp'])) {
            $this->log('error', 'Invalid timestamp response format', [
                'client_id' => $clientId,
                'response' => $response
            ]);
            throw new \Exception('Invalid timestamp response format');
        }

        return $data['Timestamp'];
    }

    private function storeTimestamp($clientId, $timestamp)
    {
        $cacheKey = self::CACHE_PREFIX . $clientId;
        $expiresAt = time() + self::TIMESTAMP_CACHE_TTL;
        
        $timestampData = json_encode([
            'timestamp' => $timestamp,
            'expires_at' => $expiresAt,
            'client_id' => $clientId,
            'created_at' => time()
        ]);

        Cache::put($cacheKey, $timestampData, self::TIMESTAMP_CACHE_TTL);

        $this->log('info', 'Timestamp stored in cache', [
            'client_id' => $clientId,
            'cache_key' => $cacheKey,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt)
        ]);
    }

    /**
     * Reset timestamp expiration after successful MaToMa API call
     * This extends the timestamp validity for another 5 minutes
     *
     * @param string $clientId
     * @return bool
     */
    public function resetTimestampExpiration($clientId)
    {
        $cacheKey = self::CACHE_PREFIX . $clientId;
        $timestampData = Cache::get($cacheKey);
        
        if ($timestampData) {
            $data = json_decode($timestampData, true);
            if (isset($data['timestamp'])) {
                // Update expiration time
                $data['expires_at'] = time() + self::TIMESTAMP_CACHE_TTL;
                $data['last_reset'] = time();
                
                Cache::put($cacheKey, json_encode($data), self::TIMESTAMP_CACHE_TTL);
                
                $this->log('info', 'Timestamp expiration reset after successful MaToMa call', [
                    'client_id' => $clientId,
                    'new_expires_at' => date('Y-m-d H:i:s', $data['expires_at']),
                    'cache_key' => $cacheKey
                ]);
                
                return true;
            }
        }
        
        $this->log('warning', 'Failed to reset timestamp expiration - no valid timestamp found', [
            'client_id' => $clientId,
            'cache_key' => $cacheKey
        ]);
        
        return false;
    }

    /**
     * Force refresh the timestamp for a client
     * This is used when we get a session validation error
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $channel
     * @return string
     * @throws \Exception
     */
    public function forceRefreshTimestamp($clientId, $clientSecret, $channel)
    {
        $this->log('info', 'Forcing timestamp refresh', [
            'client_id' => $clientId,
            'reason' => 'Session validation failed'
        ]);

        // Clear existing timestamp from cache
        $cacheKey = self::CACHE_PREFIX . $clientId;
        Cache::forget($cacheKey);

        // Fetch new timestamp
        $startTime = microtime(true);
        $timestamp = $this->makeTimestampRequest($clientId, $clientSecret, $channel);
        
        // Store new timestamp
        $this->storeTimestamp($clientId, $timestamp);
        
        $this->log('info', 'Timestamp force refreshed successfully', [
            'client_id' => $clientId,
            'processing_time' => microtime(true) - $startTime
        ]);

        return $timestamp;
    }

    private function log($level, $message, $context = [])
    {
        $context['request_id'] = $this->requestId;
        Log::channel($this->logChannel)->$level($message, $context);
    }
} 