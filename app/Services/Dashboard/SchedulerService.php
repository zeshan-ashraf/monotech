<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApplicationRuntimeHelper;
use App\Helpers\ProcessHelper;
use Carbon\Carbon;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Tracks Laravel scheduler execution metadata in Redis.
 */
class SchedulerService
{
    private const LOG_CHANNEL = 'payin';

    public function __construct(
        private readonly ?Connection $redis = null
    ) {
    }

    public function recordTick(): void
    {
        $this->safeRedis(function (Connection $redis): void {
            $now = now()->timestamp;
            $redis->set(ApplicationRuntimeHelper::KEY_SCHEDULER_LAST_TICK, (string) $now);
            $redis->set(
                ApplicationRuntimeHelper::KEY_SCHEDULER_NEXT_TICK,
                (string) ($now + 60)
            );
        });
    }

    public function storeScheduledCommandCount(int $count): void
    {
        $this->safeRedis(function (Connection $redis) use ($count): void {
            $redis->set(
                ApplicationRuntimeHelper::KEY_SCHEDULER_COMMAND_COUNT,
                (string) max(0, $count)
            );
        });
    }

    public function recordCommandStart(string $command, ?int $pid = null): void
    {
        $this->safeRedis(function (Connection $redis) use ($command, $pid): void {
            $payload = json_encode([
                'command' => $command,
                'started_at' => now()->timestamp,
                'pid' => $pid,
            ]);

            $redis->hset(ApplicationRuntimeHelper::KEY_SCHEDULER_RUNNING, $command, $payload);
            $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_SCHEDULER_RUNNING);
        });
    }

    public function recordCommandEnd(string $command, string $status, float $durationSeconds): void
    {
        $this->safeRedis(function (Connection $redis) use ($command, $status, $durationSeconds): void {
            $redis->hdel(ApplicationRuntimeHelper::KEY_SCHEDULER_RUNNING, $command);
            $redis->lpush(ApplicationRuntimeHelper::KEY_SCHEDULER_DURATIONS, (string) round($durationSeconds, 2));
            $redis->ltrim(ApplicationRuntimeHelper::KEY_SCHEDULER_DURATIONS, 0, 99);
            $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_SCHEDULER_DURATIONS);

            if ($status === ApplicationRuntimeHelper::STATUS_CRITICAL) {
                $this->incrementFailedToday($redis, $command);
            }
        });
    }

    public function recordCommandFailure(string $command, string $message = ''): void
    {
        $this->safeRedis(function (Connection $redis) use ($command, $message): void {
            $redis->hdel(ApplicationRuntimeHelper::KEY_SCHEDULER_RUNNING, $command);
            $this->incrementFailedToday($redis, $command);

            $redis->set(ApplicationRuntimeHelper::KEY_SCHEDULER_LAST_FAILED, json_encode([
                'command' => $command,
                'message' => $message,
                'failed_at' => now()->timestamp,
            ]));
            $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_SCHEDULER_LAST_FAILED);
        });
    }

  /**
   * @return array<string, mixed>
   */
    public function collect(): array
    {
        try {
            $redis = $this->connection();
            $lastTick = (int) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_LAST_TICK) ?: 0);
            $nextTick = (int) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_NEXT_TICK) ?: 0);
            $commandCount = (int) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_COMMAND_COUNT) ?: 0);
            $failedToday = $this->failedCountToday($redis);
            $running = $this->runningCommands($redis);
            $durations = $this->recentDurations($redis);
            $lastFailed = $this->lastFailedCommand($redis);

            $secondsSinceTick = $lastTick > 0 ? now()->timestamp - $lastTick : null;
            $schedulerRunning = ProcessHelper::isSchedulerRunning() || $running !== [];
            $status = $this->resolveStatus($secondsSinceTick, $failedToday, $running);

            $avgRuntime = $durations !== []
                ? round(array_sum($durations) / count($durations), 2)
                : 0.0;

            $longestRuntime = $durations !== [] ? max($durations) : 0.0;

            return [
                'available' => true,
                'status' => $status,
                'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                'status_color' => ApplicationRuntimeHelper::statusColor($status),
                'scheduler_running' => $schedulerRunning,
                'last_tick' => $lastTick > 0 ? ApplicationRuntimeHelper::formatDateTime($lastTick) : ApplicationRuntimeHelper::unavailableMetric(),
                'last_tick_at' => $lastTick,
                'next_tick' => $nextTick > 0 ? ApplicationRuntimeHelper::formatDateTime($nextTick) : ApplicationRuntimeHelper::unavailableMetric(),
                'next_tick_at' => $nextTick,
                'seconds_since_tick' => $secondsSinceTick,
                'scheduled_commands' => $commandCount,
                'running_commands' => count($running),
                'running_command_list' => $running,
                'failed_today' => $failedToday,
                'avg_runtime_seconds' => $avgRuntime,
                'avg_runtime' => ApplicationRuntimeHelper::formatDuration((int) round($avgRuntime)),
                'longest_runtime_seconds' => $longestRuntime,
                'longest_runtime' => ApplicationRuntimeHelper::formatDuration((int) round($longestRuntime)),
                'last_failed' => $lastFailed,
            ];
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Scheduler metrics collection failed', [
                'message' => $exception->getMessage(),
            ]);

            return $this->unavailablePayload();
        }
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    public function longRunningCommands(int $thresholdSeconds): array
    {
        try {
            $running = $this->runningCommands($this->connection());
            $stuck = [];

            foreach ($running as $command) {
                $startedAt = (int) ($command['started_at'] ?? 0);
                $duration = $startedAt > 0 ? now()->timestamp - $startedAt : 0;

                if ($duration < $thresholdSeconds) {
                    continue;
                }

                $status = ApplicationRuntimeHelper::durationStatus(
                    $duration,
                    $thresholdSeconds,
                    (int) config('application_runtime.scheduler.command_warning_seconds', 600) * 2
                );

                $stuck[] = [
                    'type' => ApplicationRuntimeHelper::TYPE_SCHEDULER,
                    'type_label' => 'Scheduler',
                    'name' => (string) ($command['command'] ?? 'Unknown'),
                    'pid' => $command['pid'] ?? null,
                    'started_at' => $startedAt,
                    'started' => ApplicationRuntimeHelper::formatTime($startedAt),
                    'duration_seconds' => $duration,
                    'running_for' => ApplicationRuntimeHelper::formatDuration($duration),
                    'status' => $status,
                    'status_label' => ApplicationRuntimeHelper::statusLabel($status),
                    'status_color' => ApplicationRuntimeHelper::statusColor($status),
                    'recommendation' => $status === ApplicationRuntimeHelper::STATUS_CRITICAL
                        ? 'Investigate'
                        : 'Monitor Command',
                ];
            }

            return $stuck;
        } catch (Throwable) {
            return [];
        }
    }

    private function connection(): Connection
    {
        return $this->redis ?? Redis::connection(ApplicationRuntimeHelper::redisConnection());
    }

    private function resolveStatus(?int $secondsSinceTick, int $failedToday, array $running): string
    {
        $warning = (int) config('application_runtime.scheduler.tick_warning_seconds', 120);
        $critical = (int) config('application_runtime.scheduler.tick_critical_seconds', 300);

        if ($failedToday >= 3) {
            return ApplicationRuntimeHelper::STATUS_CRITICAL;
        }

        if ($secondsSinceTick !== null && $secondsSinceTick >= $critical) {
            return ApplicationRuntimeHelper::STATUS_CRITICAL;
        }

        if ($failedToday > 0 || ($secondsSinceTick !== null && $secondsSinceTick >= $warning)) {
            return ApplicationRuntimeHelper::STATUS_WARNING;
        }

        if ($running !== []) {
            return ApplicationRuntimeHelper::STATUS_WARNING;
        }

        return ApplicationRuntimeHelper::STATUS_HEALTHY;
    }

  /**
   * @return array<int, array<string, mixed>>
   */
    private function runningCommands(Connection $redis): array
    {
        $raw = $redis->hgetall(ApplicationRuntimeHelper::KEY_SCHEDULER_RUNNING) ?: [];
        $commands = [];

        foreach ($raw as $command => $payload) {
            $decoded = json_decode((string) $payload, true);

            if (! is_array($decoded)) {
                continue;
            }

            $decoded['command'] = (string) $command;
            $commands[] = $decoded;
        }

        return $commands;
    }

  /**
   * @return array<int, float>
   */
    private function recentDurations(Connection $redis): array
    {
        $values = $redis->lrange(ApplicationRuntimeHelper::KEY_SCHEDULER_DURATIONS, 0, 49) ?: [];

        return array_values(array_filter(array_map(static fn ($value) => is_numeric($value) ? (float) $value : null, $values)));
    }

  /**
   * @return array<string, mixed>|null
   */
    private function lastFailedCommand(Connection $redis): ?array
    {
        $raw = $redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_LAST_FAILED);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $failedAt = (int) ($decoded['failed_at'] ?? 0);

        return [
            'command' => (string) ($decoded['command'] ?? 'Unknown'),
            'message' => (string) ($decoded['message'] ?? ''),
            'failed_at' => ApplicationRuntimeHelper::formatDateTime($failedAt),
        ];
    }

    private function failedCountToday(Connection $redis): int
    {
        $today = Carbon::now()->toDateString();
        $storedDate = (string) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_DATE) ?: '');

        if ($storedDate !== $today) {
            return 0;
        }

        return (int) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_TODAY) ?: 0);
    }

    private function incrementFailedToday(Connection $redis, string $command): void
    {
        $today = Carbon::now()->toDateString();
        $storedDate = (string) ($redis->get(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_DATE) ?: '');

        if ($storedDate !== $today) {
            $redis->set(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_DATE, $today);
            $redis->set(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_TODAY, '1');
        } else {
            $redis->incr(ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_TODAY);
        }

        $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_TODAY);
        $this->refreshMetadataTtl($redis, ApplicationRuntimeHelper::KEY_SCHEDULER_FAILED_DATE);
    }

    private function refreshMetadataTtl(Connection $redis, string $key): void
    {
        $ttl = (int) config('application_runtime.scheduler.metadata_ttl_seconds', 86_400);
        $redis->expire($key, max(300, $ttl));
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
            'scheduler_running' => false,
            'last_tick' => ApplicationRuntimeHelper::unavailableMetric(),
            'last_tick_at' => null,
            'next_tick' => ApplicationRuntimeHelper::unavailableMetric(),
            'next_tick_at' => null,
            'seconds_since_tick' => null,
            'scheduled_commands' => ApplicationRuntimeHelper::unavailableMetric(),
            'running_commands' => 0,
            'running_command_list' => [],
            'failed_today' => 0,
            'avg_runtime_seconds' => 0,
            'avg_runtime' => ApplicationRuntimeHelper::unavailableMetric(),
            'longest_runtime_seconds' => 0,
            'longest_runtime' => ApplicationRuntimeHelper::unavailableMetric(),
            'last_failed' => null,
        ];
    }

    private function safeRedis(callable $callback): void
    {
        try {
            $callback($this->connection());
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Scheduler metrics write failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
