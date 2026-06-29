<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApplicationRuntimeHelper;
use App\Helpers\ProcessHelper;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Detects long-running scheduler commands, queue jobs, PHP requests, and gateway calls.
 */
class ProcessMonitorService
{
    private const LOG_CHANNEL = 'payin';

    public function __construct(
        private readonly SchedulerService $schedulerService,
        private readonly QueueMonitorService $queueMonitorService,
        private readonly ?Connection $redis = null
    ) {
    }

  /**
   * @return array<string, mixed>
   */
    public function collect(): array
    {
        return $this->remember(ApplicationRuntimeHelper::KEY_PROCESS_CACHE, function (): array {
            $processes = $this->detectStuckProcesses();
            $criticalCount = count(array_filter($processes, static fn (array $item) => $item['status'] === ApplicationRuntimeHelper::STATUS_CRITICAL));
            $warningCount = count(array_filter($processes, static fn (array $item) => $item['status'] === ApplicationRuntimeHelper::STATUS_WARNING));
            $total = count($processes);

            $status = ApplicationRuntimeHelper::STATUS_HEALTHY;

            if ($criticalCount > 0) {
                $status = ApplicationRuntimeHelper::STATUS_CRITICAL;
            } elseif ($warningCount > 0 || $total > 0) {
                $status = $warningCount > 0 ? ApplicationRuntimeHelper::STATUS_WARNING : ApplicationRuntimeHelper::STATUS_HEALTHY;
            }

            if ($total > 0 && $status === ApplicationRuntimeHelper::STATUS_HEALTHY) {
                $status = ApplicationRuntimeHelper::STATUS_WARNING;
            }

            return [
                'status' => $status,
                'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                'status_color' => ApplicationRuntimeHelper::statusColor($status),
                'total' => $total,
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'processes' => $processes,
            ];
        });
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    public function detectStuckProcesses(): array
    {
        $processes = [];

        try {
            $processes = array_merge(
                $processes,
                $this->schedulerService->longRunningCommands(
                    (int) config('application_runtime.process.scheduler_command_seconds', 600)
                ),
                $this->queueMonitorService->longRunningJobs(
                    (int) config('application_runtime.process.queue_job_seconds', 300)
                ),
                $this->longRunningGatewayRequests(),
                $this->longRunningPhpRequests()
            );
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Stuck process detection failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        usort($processes, static fn (array $a, array $b) => ($b['duration_seconds'] ?? 0) <=> ($a['duration_seconds'] ?? 0));

        return $processes;
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    private function longRunningGatewayRequests(): array
    {
        try {
            $redis = $this->connection();
            $raw = $redis->hgetall(ApplicationRuntimeHelper::KEY_GATEWAY_PROCESSING) ?: [];
            $threshold = (int) config('application_runtime.process.gateway_request_seconds', 60);
            $stuck = [];

            foreach ($raw as $requestId => $payload) {
                $decoded = json_decode((string) $payload, true);

                if (! is_array($decoded)) {
                    continue;
                }

                $startedAt = (int) ($decoded['started_at'] ?? 0);
                $duration = $startedAt > 0 ? now()->timestamp - $startedAt : 0;

                if ($duration < $threshold) {
                    continue;
                }

                $status = ApplicationRuntimeHelper::durationStatus($duration, $threshold, $threshold * 2);

                $stuck[] = [
                    'type' => ApplicationRuntimeHelper::TYPE_GATEWAY,
                    'type_label' => 'Gateway',
                    'name' => (string) ($decoded['name'] ?? 'Gateway Request'),
                    'pid' => $decoded['pid'] ?? null,
                    'started_at' => $startedAt,
                    'started' => ApplicationRuntimeHelper::formatTime($startedAt),
                    'duration_seconds' => $duration,
                    'running_for' => ApplicationRuntimeHelper::formatDuration($duration),
                    'status' => $status,
                    'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                    'status_color' => ApplicationRuntimeHelper::statusColor($status),
                    'recommendation' => 'Investigate Long Running Gateway Request',
                ];
            }

            return $stuck;
        } catch (Throwable) {
            return [];
        }
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    private function longRunningPhpRequests(): array
    {
        $threshold = (int) config('application_runtime.process.php_request_seconds', 30);
        $matches = ProcessHelper::findLongRunningPhpProcesses($threshold);
        $stuck = [];

        foreach ($matches as $match) {
            if (str_contains($match['command'], 'queue:work') || str_contains($match['command'], 'schedule:run')) {
                continue;
            }

            $duration = (int) $match['elapsed_seconds'];
            $status = ApplicationRuntimeHelper::durationStatus($duration, $threshold, $threshold * 3);

            $stuck[] = [
                'type' => ApplicationRuntimeHelper::TYPE_PHP_REQUEST,
                'type_label' => 'PHP',
                'name' => $this->shortenCommand($match['command']),
                'pid' => $match['pid'],
                'started_at' => now()->timestamp - $duration,
                'started' => ApplicationRuntimeHelper::formatTime(now()->timestamp - $duration),
                'duration_seconds' => $duration,
                'running_for' => ApplicationRuntimeHelper::formatDuration($duration),
                'status' => $status,
                'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                'status_color' => ApplicationRuntimeHelper::statusColor($status),
                'recommendation' => $status === ApplicationRuntimeHelper::STATUS_CRITICAL
                    ? 'Investigate Request'
                    : 'Monitor Request',
            ];
        }

        return $stuck;
    }

    private function shortenCommand(string $command): string
    {
        if (strlen($command) <= 80) {
            return $command;
        }

        return substr($command, 0, 77) . '...';
    }

    private function connection(): Connection
    {
        return $this->redis ?? Redis::connection(ApplicationRuntimeHelper::redisConnection());
    }

  /**
   * @param  callable(): array<string, mixed>  $callback
   * @return array<string, mixed>
   */
    private function remember(string $cacheKey, callable $callback): array
    {
        try {
            $connection = $this->connection();
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
        } catch (Throwable) {
            return $callback();
        }
    }
}
