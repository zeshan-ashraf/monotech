<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApplicationRuntimeHelper;
use App\Helpers\LinuxHelper;
use App\Helpers\ProcessHelper;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Monitors Laravel queue depth, workers, and in-flight jobs.
 */
class QueueMonitorService
{
    private const LOG_CHANNEL = 'payin';

    public function __construct(
        private readonly ?Connection $redis = null
    ) {
    }

    public function recordJobStart(string $jobId, string $jobName, ?int $pid = null): void
    {
        $this->safeRedis(function (Connection $redis) use ($jobId, $jobName, $pid): void {
            $payload = json_encode([
                'name' => $jobName,
                'started_at' => now()->timestamp,
                'pid' => $pid,
            ]);

            $redis->hset(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, $jobId, $payload);
            $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING);
        });
    }

    public function recordJobEnd(string $jobId, float $durationSeconds): void
    {
        $this->safeRedis(function (Connection $redis) use ($jobId, $durationSeconds): void {
            $redis->hdel(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, $jobId);
            $redis->lpush(ApplicationRuntimeHelper::KEY_QUEUE_DURATIONS, (string) round($durationSeconds, 2));
            $redis->ltrim(ApplicationRuntimeHelper::KEY_QUEUE_DURATIONS, 0, 99);
            $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_QUEUE_DURATIONS);
        });
    }

    public function recordJobFailure(string $jobId): void
    {
        $this->safeRedis(function (Connection $redis) use ($jobId): void {
            $redis->hdel(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, $jobId);
        });
    }

  /**
   * @return array<string, mixed>
   */
    public function collect(): array
    {
        return $this->remember(ApplicationRuntimeHelper::KEY_QUEUE_CACHE, function (): array {
            try {
                $connection = (string) config('application_runtime.queue.connection', config('queue.default'));
                $queues = ApplicationRuntimeHelper::monitoredQueues();
                $pending = $this->pendingJobs($connection, $queues);
                $processing = $this->processingJobsCount();
                $failed = $this->failedJobsCount();
                $retrying = $this->retryingJobsCount($connection, $queues);
                $workerCount = $this->workerCount();
                $durations = $this->recentDurations();
                $longest = $this->longestRunningJob();

                $avgRuntime = $durations !== []
                    ? round(array_sum($durations) / count($durations), 2)
                    : 0.0;

                $status = $this->resolveStatus($pending, $failed, $processing, $longest);

                return [
                    'available' => true,
                    'status' => $status,
                    'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                    'status_color' => ApplicationRuntimeHelper::statusColor($status),
                    'connection' => $connection,
                    'pending_jobs' => $pending,
                    'processing_jobs' => $processing,
                    'failed_jobs' => $failed,
                    'retrying_jobs' => $retrying,
                    'avg_runtime_seconds' => $avgRuntime,
                    'avg_runtime' => ApplicationRuntimeHelper::formatDuration((int) round($avgRuntime)),
                    'longest_running_seconds' => $longest['duration_seconds'] ?? 0,
                    'longest_running' => $longest['name'] ?? ApplicationRuntimeHelper::unavailableMetric(),
                    'longest_running_for' => isset($longest['duration_seconds'])
                        ? ApplicationRuntimeHelper::formatDuration((int) $longest['duration_seconds'])
                        : ApplicationRuntimeHelper::unavailableMetric(),
                    'worker_count' => $workerCount,
                ];
            } catch (Throwable $exception) {
                Log::channel(self::LOG_CHANNEL)->warning('Queue metrics collection failed', [
                    'message' => $exception->getMessage(),
                ]);

                return $this->unavailablePayload();
            }
        });
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    public function longRunningJobs(int $thresholdSeconds): array
    {
        try {
            $redis = $this->connection();
            $raw = $redis->hgetall(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING) ?: [];
            $stuck = [];

            foreach ($raw as $jobId => $payload) {
                $decoded = json_decode((string) $payload, true);

                if (! is_array($decoded)) {
                    continue;
                }

                $startedAt = (int) ($decoded['started_at'] ?? 0);
                $duration = $startedAt > 0 ? now()->timestamp - $startedAt : 0;

                if ($duration < $thresholdSeconds) {
                    continue;
                }

                $status = ApplicationRuntimeHelper::durationStatus(
                    $duration,
                    $thresholdSeconds,
                    $thresholdSeconds * 2
                );

                $stuck[] = [
                    'type' => ApplicationRuntimeHelper::TYPE_QUEUE_JOB,
                    'type_label' => 'Queue',
                    'name' => (string) ($decoded['name'] ?? 'Unknown Job'),
                    'pid' => $decoded['pid'] ?? null,
                    'job_id' => (string) $jobId,
                    'started_at' => $startedAt,
                    'started' => ApplicationRuntimeHelper::formatTime($startedAt),
                    'duration_seconds' => $duration,
                    'running_for' => ApplicationRuntimeHelper::formatDuration($duration),
                    'status' => $status,
                    'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                    'status_color' => ApplicationRuntimeHelper::statusColor($status),
                    'recommendation' => 'Restart Worker',
                ];
            }

            return $stuck;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<int, string>  $queues
     */
    private function pendingJobs(string $connection, array $queues): int
    {
        if ($connection !== 'redis') {
            return 0;
        }

        $total = 0;

        foreach ($queues as $queue) {
            try {
                $total += (int) $this->queueRedisConnection()->llen($this->queueKey($queue));
            } catch (Throwable) {
                continue;
            }
        }

        return $total;
    }

    /**
     * @param  array<int, string>  $queues
     */
    private function retryingJobsCount(string $connection, array $queues): int
    {
        if ($connection !== 'redis') {
            return 0;
        }

        $total = 0;

        foreach ($queues as $queue) {
            try {
                $total += (int) $this->queueRedisConnection()->zcard($this->delayedKey($queue));
            } catch (Throwable) {
                continue;
            }
        }

        return $total;
    }

    private function processingJobsCount(): int
    {
        try {
            return (int) $this->connection()->hlen(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING);
        } catch (Throwable) {
            return 0;
        }
    }

    private function failedJobsCount(): int
    {
        $cacheKey = 'runtime:cache:failed_jobs_count';
        $cacheTtl = (int) config('application_runtime.queue.failed_jobs_cache_seconds', 30);

        try {
            $redis = $this->connection();
            $cached = $redis->get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                return (int) $cached;
            }

            $count = (int) DB::table(config('queue.failed.table', 'failed_jobs'))->count();
            $redis->setex($cacheKey, max(5, $cacheTtl), (string) $count);

            return $count;
        } catch (Throwable) {
            return 0;
        }
    }

    private function workerCount(): int|string
    {
        if ((bool) config('application_runtime.supervisor.enabled', false)) {
            $supervisorCount = $this->supervisorWorkerCount();

            if ($supervisorCount !== null) {
                return $supervisorCount;
            }
        }

        $count = ProcessHelper::countQueueWorkers();

        return $count ?? ApplicationRuntimeHelper::unavailableMetric();
    }

    private function supervisorWorkerCount(): ?int
    {
        $command = (string) config('application_runtime.supervisor.status_command', '');

        if ($command === '') {
            return null;
        }

        $output = LinuxHelper::run(explode(' ', $command));

        if ($output === null) {
            return null;
        }

        $running = 0;

        foreach (explode("\n", $output) as $line) {
            if (str_contains(strtolower($line), 'run')) {
                $running++;
            }
        }

        return $running;
    }

  /**
   * @return array<int, float>
   */
    private function recentDurations(): array
    {
        try {
            $values = $this->connection()->lrange(ApplicationRuntimeHelper::KEY_QUEUE_DURATIONS, 0, 49) ?: [];

            return array_values(array_filter(array_map(static fn ($value) => is_numeric($value) ? (float) $value : null, $values)));
        } catch (Throwable) {
            return [];
        }
    }

  /**
   * @return array{name?: string, duration_seconds?: int}
   */
    private function longestRunningJob(): array
    {
        try {
            $raw = $this->connection()->hgetall(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING) ?: [];
            $longest = ['name' => null, 'duration_seconds' => 0];

            foreach ($raw as $payload) {
                $decoded = json_decode((string) $payload, true);

                if (! is_array($decoded)) {
                    continue;
                }

                $startedAt = (int) ($decoded['started_at'] ?? 0);
                $duration = $startedAt > 0 ? now()->timestamp - $startedAt : 0;

                if ($duration > $longest['duration_seconds']) {
                    $longest = [
                        'name' => (string) ($decoded['name'] ?? 'Unknown Job'),
                        'duration_seconds' => $duration,
                    ];
                }
            }

            return $longest;
        } catch (Throwable) {
            return [];
        }
    }

    private function resolveStatus(int $pending, int $failed, int $processing, array $longest): string
    {
        $jobWarning = (int) config('application_runtime.queue.job_warning_seconds', 300);
        $longestDuration = (int) ($longest['duration_seconds'] ?? 0);

        if ($failed >= 10 || $longestDuration >= $jobWarning * 2) {
            return ApplicationRuntimeHelper::STATUS_CRITICAL;
        }

        if ($failed > 0 || $pending > 100 || $longestDuration >= $jobWarning) {
            return ApplicationRuntimeHelper::STATUS_WARNING;
        }

        return ApplicationRuntimeHelper::STATUS_HEALTHY;
    }

    private function queueKey(string $queue): string
    {
        $prefix = (string) config('database.redis.options.prefix', '');

        return $prefix . 'queues:' . $queue;
    }

    private function delayedKey(string $queue): string
    {
        $prefix = (string) config('database.redis.options.prefix', '');

        return $prefix . 'queues:' . $queue . ':delayed';
    }

    private function connection(): Connection
    {
        return $this->redis ?? Redis::connection(ApplicationRuntimeHelper::redisConnection());
    }

    private function queueRedisConnection(): Connection
    {
        $queueConnection = (string) config('queue.connections.redis.connection', 'default');

        return Redis::connection($queueConnection);
    }

    private function refreshMetadataTtl(Connection $redis, string $key): void
    {
        $redis->expire($key, (int) config('application_runtime.scheduler.metadata_ttl_seconds', 86_400));
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
            'connection' => (string) config('application_runtime.queue.connection', 'sync'),
            'pending_jobs' => ApplicationRuntimeHelper::unavailableMetric(),
            'processing_jobs' => 0,
            'failed_jobs' => 0,
            'retrying_jobs' => 0,
            'avg_runtime_seconds' => 0,
            'avg_runtime' => ApplicationRuntimeHelper::unavailableMetric(),
            'longest_running_seconds' => 0,
            'longest_running' => ApplicationRuntimeHelper::unavailableMetric(),
            'longest_running_for' => ApplicationRuntimeHelper::unavailableMetric(),
            'worker_count' => ApplicationRuntimeHelper::unavailableMetric(),
        ];
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
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Queue metrics cache failed', [
                'message' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }

    private function safeRedis(callable $callback): void
    {
        try {
            $callback($this->connection());
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Queue metrics write failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
