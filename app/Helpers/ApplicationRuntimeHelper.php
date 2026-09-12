<?php

declare(strict_types=1);

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Constants, Redis key builders, and formatting for application runtime monitoring.
 */
class ApplicationRuntimeHelper
{
    public const STATUS_HEALTHY = 'healthy';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    public const TYPE_PHP_REQUEST = 'php_request';

    public const TYPE_QUEUE_JOB = 'queue';

    public const TYPE_SCHEDULER = 'scheduler';

    public const TYPE_GATEWAY = 'gateway';

    public const TYPE_DATABASE = 'database';

    public const KEY_SCHEDULER_LAST_TICK = 'runtime:scheduler:last_tick';

    public const KEY_SCHEDULER_NEXT_TICK = 'runtime:scheduler:next_tick';

    public const KEY_SCHEDULER_RUNNING = 'runtime:scheduler:running';

    public const KEY_SCHEDULER_FAILED_TODAY = 'runtime:scheduler:failed_today';

    public const KEY_SCHEDULER_FAILED_DATE = 'runtime:scheduler:failed_date';

    public const KEY_SCHEDULER_LAST_FAILED = 'runtime:scheduler:last_failed';

    public const KEY_SCHEDULER_DURATIONS = 'runtime:scheduler:durations';

    public const KEY_SCHEDULER_COMMAND_COUNT = 'runtime:scheduler:command_count';

    public const KEY_QUEUE_PROCESSING = 'runtime:queue:processing';

    public const KEY_QUEUE_DURATIONS = 'runtime:queue:durations';

    public const KEY_GATEWAY_PROCESSING = 'runtime:gateway:processing';

    public const KEY_PHP_FPM_CACHE = 'runtime:cache:php_fpm';

    public const KEY_QUEUE_CACHE = 'runtime:cache:queue';

    public const KEY_PROCESS_CACHE = 'runtime:cache:process';

    public const KEY_SUPERVISOR_WORKERS = 'runtime:supervisor:workers';

    /**
     * @return non-empty-string
     */
    public static function cacheTtl(): int
    {
        return max(1, (int) config('application_runtime.cache_ttl_seconds', 5));
    }

  /**
   * @return non-empty-string
   */
    public static function redisConnection(): string
    {
        return (string) config('application_runtime.redis_connection', 'metrics');
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_WARNING => 'warning',
            self::STATUS_CRITICAL => 'danger',
            default => 'success',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_WARNING => 'Warning',
            self::STATUS_CRITICAL => 'Critical',
            default => 'Healthy',
        };
    }

    public static function utilizationStatus(float $utilization): string
    {
        $critical = (float) config('application_runtime.php_fpm.critical_threshold_utilization', 90);
        $warning = (float) config('application_runtime.php_fpm.slow_threshold_utilization', 70);

        if ($utilization >= $critical) {
            return self::STATUS_CRITICAL;
        }

        if ($utilization >= $warning) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_HEALTHY;
    }

    public static function durationStatus(int $seconds, int $warningThreshold, int $criticalThreshold): string
    {
        if ($seconds >= $criticalThreshold) {
            return self::STATUS_CRITICAL;
        }

        if ($seconds >= $warningThreshold) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_HEALTHY;
    }

    /**
     * @return non-empty-string
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        if ($minutes < 60) {
            return $remaining > 0
                ? $minutes . ' min ' . $remaining . ' sec'
                : $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        return $hours . ' hr ' . $minutes . ' min';
    }

    /**
     * @return non-empty-string
     */
    public static function formatTime(?int $timestamp): string
    {
        if ($timestamp === null || $timestamp <= 0) {
            return 'Unavailable';
        }

        return Carbon::createFromTimestamp($timestamp)->format('g:i A');
    }

    /**
     * @return non-empty-string
     */
    public static function formatDateTime(?int $timestamp): string
    {
        if ($timestamp === null || $timestamp <= 0) {
            return 'Unavailable';
        }

        return Carbon::createFromTimestamp($timestamp)->format('M j, g:i A');
    }

    public static function unavailableMetric(): string
    {
        return 'Unavailable';
    }

    /**
     * @param  array<int, string>  $queues
     * @return array<int, string>
     */
    public static function monitoredQueues(?array $queues = null): array
    {
        $queues ??= (array) config('application_runtime.queue.queues', ['default']);

        $queues = array_values(array_filter($queues, static fn ($queue) => is_string($queue) && $queue !== ''));

        return $queues !== [] ? $queues : ['default'];
    }
}
