<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApplicationRuntimeHelper;
use App\Helpers\LinuxHelper;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Collects PHP-FPM pool metrics from the status endpoint with safe fallbacks.
 */
class PHPFpmService
{
    private const LOG_CHANNEL = 'payin';

    public function __construct(
        private readonly ?Connection $redis = null
    ) {
    }

  /**
   * @return array<string, mixed>
   */
    public function collect(): array
    {
        return $this->remember(ApplicationRuntimeHelper::KEY_PHP_FPM_CACHE, function (): array {
            $status = $this->fetchStatus();

            if ($status === null) {
                return $this->unavailablePayload();
            }

            return $this->buildPayload($status);
        });
    }

  /**
   * @return array<string, mixed>|null
   */
    private function fetchStatus(): ?array
    {
        $json = $this->fetchStatusJson();

        if ($json !== null) {
            return $json;
        }

        return $this->parseTextStatus($this->fetchStatusText());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchStatusJson(): ?array
    {
        $baseUrl = (string) config('application_runtime.php_fpm.status_url', '');

        if ($baseUrl === '') {
            return null;
        }

        $url = str_contains($baseUrl, '?') ? $baseUrl . '&json' : rtrim($baseUrl, '/') . '?json';
        $body = LinuxHelper::fetchUrl($url);

        if ($body === null) {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchStatusText(): ?string
    {
        $baseUrl = (string) config('application_runtime.php_fpm.status_url', '');

        if ($baseUrl === '') {
            return null;
        }

        $url = str_contains($baseUrl, '?') ? strstr($baseUrl, '?', true) ?: $baseUrl : $baseUrl;

        return LinuxHelper::fetchUrl($url);
    }

  /**
   * @return array<string, mixed>|null
   */
    private function parseTextStatus(?string $body): ?array
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        $parsed = [];

        foreach (explode("\n", $body) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $parsed[$this->normalizeKey($key)] = $value;
        }

        return $parsed !== [] ? $parsed : null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(str_replace(' ', '_', $key));
    }

  /**
   * @param  array<string, mixed>  $status
   * @return array<string, mixed>
   */
    private function buildPayload(array $status): array
    {
        $totalWorkers = $this->intValue($status, ['total_processes', 'total_workers'], 0);
        $busyWorkers = $this->intValue($status, ['active_processes', 'busy_workers', 'active'], 0);
        $idleWorkers = $this->intValue($status, ['idle_processes', 'idle_workers', 'idle'], max(0, $totalWorkers - $busyWorkers));
        $listenQueue = $this->intValue($status, ['listen_queue', 'queue'], 0);
        $maxChildrenReached = $this->intValue($status, ['max_children_reached', 'max_children_hit'], 0);
        $slowRequests = $this->intValue($status, ['slow_requests'], 0);
        $acceptedConn = $this->intValue($status, ['accepted_conn', 'accepted_connections'], 0);
        $startSince = $this->intValue($status, ['start_since'], 0);

        if ($totalWorkers <= 0) {
            $totalWorkers = max(1, $busyWorkers + $idleWorkers);
        }

        $requestsPerSecond = $startSince > 0
            ? round($acceptedConn / $startSince, 1)
            : 0.0;

        $avgResponseMs = $this->estimateAverageResponseMs($status, $requestsPerSecond);
        $utilization = $totalWorkers > 0
            ? round(($busyWorkers / $totalWorkers) * 100, 1)
            : 0.0;

        $health = ApplicationRuntimeHelper::utilizationStatus($utilization);

        if ($listenQueue > 0 && $health === ApplicationRuntimeHelper::STATUS_HEALTHY) {
            $health = ApplicationRuntimeHelper::STATUS_WARNING;
        }

        if ($maxChildrenReached > 0) {
            $health = ApplicationRuntimeHelper::STATUS_CRITICAL;
        }

        return [
            'available' => true,
            'status' => $health,
            'status_label' => ApplicationRuntimeHelper::statusLabel($health),
            'status_color' => ApplicationRuntimeHelper::statusColor($health),
            'total_workers' => $totalWorkers,
            'busy_workers' => $busyWorkers,
            'idle_workers' => $idleWorkers,
            'listen_queue' => $listenQueue,
            'max_children_reached' => $maxChildrenReached,
            'slow_requests' => $slowRequests,
            'requests_per_second' => $requestsPerSecond,
            'avg_response_ms' => $avgResponseMs,
            'worker_utilization' => $utilization,
        ];
    }

  /**
   * @param  array<string, mixed>  $status
   * @param  array<int, string>  $keys
   */
    private function intValue(array $status, array $keys, int $default = 0): int
    {
        foreach ($keys as $key) {
            if (isset($status[$key]) && is_numeric($status[$key])) {
                return (int) $status[$key];
            }
        }

        return $default;
    }

  /**
   * @param  array<string, mixed>  $status
   */
    private function estimateAverageResponseMs(array $status, float $requestsPerSecond): float
    {
        foreach (['avg_response_time', 'avg_response_ms'] as $key) {
            if (isset($status[$key]) && is_numeric($status[$key])) {
                $value = (float) $status[$key];

                return $value > 100 ? round($value, 0) : round($value * 1000, 0);
            }
        }

        if ($requestsPerSecond <= 0) {
            return 0.0;
        }

        return round(min(5000, 1000 / max(0.1, $requestsPerSecond)), 0);
    }

  /**
   * @return array<string, mixed>
   */
    private function unavailablePayload(): array
    {
        return [
            'available' => false,
            'status' => ApplicationRuntimeHelper::STATUS_WARNING,
            'status_label' => 'Unavailable',
            'status_color' => 'secondary',
            'total_workers' => ApplicationRuntimeHelper::unavailableMetric(),
            'busy_workers' => ApplicationRuntimeHelper::unavailableMetric(),
            'idle_workers' => ApplicationRuntimeHelper::unavailableMetric(),
            'listen_queue' => ApplicationRuntimeHelper::unavailableMetric(),
            'max_children_reached' => ApplicationRuntimeHelper::unavailableMetric(),
            'slow_requests' => ApplicationRuntimeHelper::unavailableMetric(),
            'requests_per_second' => ApplicationRuntimeHelper::unavailableMetric(),
            'avg_response_ms' => ApplicationRuntimeHelper::unavailableMetric(),
            'worker_utilization' => 0,
        ];
    }

  /**
   * @param  callable(): array<string, mixed>  $callback
   * @return array<string, mixed>
   */
    private function remember(string $cacheKey, callable $callback): array
    {
        try {
            $connection = $this->redis ?? Redis::connection(ApplicationRuntimeHelper::redisConnection());
            $cached = $connection->get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                $decoded = json_decode($cached, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            $payload = $callback();
            $connection->setex($cacheKey, ApplicationRuntimeHelper::cacheTtl(), json_encode($payload));

            return $payload;
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('PHP-FPM metrics cache failed', [
                'message' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }
}
