<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApplicationRuntimeHelper;

/**
 * Aggregates application runtime metrics for the OPS dashboard.
 */
class ApplicationRuntimeService
{
    public function __construct(
        private readonly PHPFpmService $phpFpmService,
        private readonly SchedulerService $schedulerService,
        private readonly QueueMonitorService $queueMonitorService,
        private readonly ProcessMonitorService $processMonitorService,
    ) {
    }

  /**
   * Full structured payload for the dashboard API.
   *
   * @return array<string, mixed>
   */
    public function collect(): array
    {
        $phpFpm = $this->phpFpmService->collect();
        $scheduler = $this->schedulerService->collect();
        $queue = $this->queueMonitorService->collect();
        $stuck = $this->processMonitorService->collect();
        $recommendations = $this->buildRecommendations($phpFpm, $scheduler, $queue, $stuck);

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->summaryCards($phpFpm, $scheduler, $queue, $stuck),
            'php_fpm' => $phpFpm,
            'scheduler' => $scheduler,
            'queue' => $queue,
            'stuck_processes' => $stuck,
            'recommendations' => $recommendations,
        ];
    }

  /**
   * @return array<string, mixed>
   */
    public function dashboardPayload(): array
    {
        return $this->collect();
    }

  /**
   * @param  array<string, mixed>  $phpFpm
   * @param  array<string, mixed>  $scheduler
   * @param  array<string, mixed>  $queue
   * @param  array<string, mixed>  $stuck
   * @return array<int, array<string, mixed>>
   */
    private function summaryCards(array $phpFpm, array $scheduler, array $queue, array $stuck): array
    {
        $busy = is_numeric($phpFpm['busy_workers'] ?? null) ? (int) $phpFpm['busy_workers'] : 0;
        $total = is_numeric($phpFpm['total_workers'] ?? null) ? (int) $phpFpm['total_workers'] : 0;

        return [
            [
                'key' => 'php_fpm',
                'title' => 'PHP-FPM',
                'status' => $phpFpm['status'] ?? ApplicationRuntimeHelper::STATUS_WARNING,
                'status_label' => $phpFpm['status_label'] ?? 'Unavailable',
                'status_color' => $phpFpm['status_color'] ?? 'secondary',
                'value' => $total > 0 ? $busy . ' / ' . $total : ($phpFpm['busy_workers'] ?? '—'),
                'subtitle' => 'Worker Utilization ' . ($phpFpm['worker_utilization'] ?? 0) . '%',
                'icon' => 'fa-code',
                'color' => $phpFpm['status_color'] ?? 'secondary',
            ],
            [
                'key' => 'scheduler',
                'title' => 'Scheduler',
                'status' => $scheduler['status'] ?? ApplicationRuntimeHelper::STATUS_WARNING,
                'status_label' => $scheduler['status_label'] ?? 'Unavailable',
                'status_color' => $scheduler['status_color'] ?? 'secondary',
                'value' => $scheduler['last_tick'] ?? ApplicationRuntimeHelper::unavailableMetric(),
                'subtitle' => 'Running ' . ($scheduler['running_commands'] ?? 0) . ' · Failed ' . ($scheduler['failed_today'] ?? 0),
                'icon' => 'fa-clock-o',
                'color' => $scheduler['status_color'] ?? 'secondary',
            ],
            [
                'key' => 'queue',
                'title' => 'Queue',
                'status' => $queue['status'] ?? ApplicationRuntimeHelper::STATUS_WARNING,
                'status_label' => $queue['status_label'] ?? 'Unavailable',
                'status_color' => $queue['status_color'] ?? 'secondary',
                'value' => is_numeric($queue['pending_jobs'] ?? null)
                    ? (int) $queue['pending_jobs'] . ' pending'
                    : (string) ($queue['pending_jobs'] ?? '—'),
                'subtitle' => 'Processing ' . ($queue['processing_jobs'] ?? 0)
                    . ' · Failed ' . ($queue['failed_jobs'] ?? 0)
                    . ' · Workers ' . ($queue['worker_count'] ?? '—'),
                'icon' => 'fa-tasks',
                'color' => $queue['status_color'] ?? 'secondary',
            ],
            [
                'key' => 'stuck',
                'title' => 'Stuck Processes',
                'status' => $stuck['status'] ?? ApplicationRuntimeHelper::STATUS_HEALTHY,
                'status_label' => $stuck['status_label'] ?? 'Healthy',
                'status_color' => $stuck['status_color'] ?? 'success',
                'value' => (string) ($stuck['total'] ?? 0),
                'subtitle' => 'Critical ' . ($stuck['critical_count'] ?? 0) . ' · Warning ' . ($stuck['warning_count'] ?? 0),
                'icon' => 'fa-exclamation-triangle',
                'color' => $stuck['status_color'] ?? 'success',
            ],
        ];
    }

  /**
   * @param  array<string, mixed>  $phpFpm
   * @param  array<string, mixed>  $scheduler
   * @param  array<string, mixed>  $queue
   * @param  array<string, mixed>  $stuck
   * @return array<int, array<string, string>>
   */
    private function buildRecommendations(array $phpFpm, array $scheduler, array $queue, array $stuck): array
    {
        $recommendations = [];

        $utilization = (float) ($phpFpm['worker_utilization'] ?? 0);
        $listenQueue = is_numeric($phpFpm['listen_queue'] ?? null) ? (int) $phpFpm['listen_queue'] : 0;
        $maxChildren = is_numeric($phpFpm['max_children_reached'] ?? null) ? (int) $phpFpm['max_children_reached'] : 0;

        if ($utilization >= 90) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_CRITICAL,
                'Increase PHP Workers',
                'PHP-FPM utilization is above 90%.',
                'Increase pm.max_children in the PHP-FPM pool configuration.'
            );
        } elseif ($utilization >= 70) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_WARNING,
                'Monitor PHP-FPM Utilization',
                'PHP-FPM worker utilization is elevated.',
                'Review traffic patterns and consider scaling workers during peak load.'
            );
        }

        if ($listenQueue > 0) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_CRITICAL,
                'PHP-FPM Saturated',
                'Listen queue has pending requests.',
                'Increase PHP-FPM workers and investigate slow endpoints.'
            );
        }

        if ($maxChildren > 0) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_CRITICAL,
                'Max Children Reached',
                'PHP-FPM max children limit has been hit.',
                'Increase pm.max_children and review worker recycling settings.'
            );
        }

        $secondsSinceTick = $scheduler['seconds_since_tick'] ?? null;

        if (is_int($secondsSinceTick) && $secondsSinceTick > 120) {
            $recommendations[] = $this->recommendation(
                $secondsSinceTick > 300 ? ApplicationRuntimeHelper::STATUS_CRITICAL : ApplicationRuntimeHelper::STATUS_WARNING,
                'Scheduler Delay Detected',
                'Scheduler has not executed recently.',
                'Check Linux cron service and verify schedule:run is configured.'
            );
        }

        if (($scheduler['failed_today'] ?? 0) > 0) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_WARNING,
                'Scheduler Failures Today',
                'One or more scheduled commands failed today.',
                'Review scheduler logs and rerun failed commands manually if needed.'
            );
        }

        if (($queue['processing_jobs'] ?? 0) > 0 && ($queue['longest_running_seconds'] ?? 0) >= 300) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_CRITICAL,
                'Restart Queue Worker',
                'A queue job has been processing for more than 5 minutes.',
                'Restart the queue worker and inspect the long-running job.'
            );
        }

        if (is_numeric($queue['failed_jobs'] ?? null) && (int) $queue['failed_jobs'] > 0) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_WARNING,
                'Failed Queue Jobs',
                'Failed jobs are present in the queue.',
                'Run queue:failed and retry or discard failed jobs after investigation.'
            );
        }

        foreach ($stuck['processes'] ?? [] as $process) {
            if (($process['type'] ?? '') !== ApplicationRuntimeHelper::TYPE_GATEWAY) {
                continue;
            }

            $recommendations[] = $this->recommendation(
                $process['status'] ?? ApplicationRuntimeHelper::STATUS_WARNING,
                'Investigate Long Running Gateway Request',
                ($process['name'] ?? 'Gateway request') . ' has been running for ' . ($process['running_for'] ?? 'a long time') . '.',
                'Inspect gateway logs and upstream provider latency.'
            );
        }

        if ($recommendations === []) {
            $recommendations[] = $this->recommendation(
                ApplicationRuntimeHelper::STATUS_HEALTHY,
                'All Systems Normal',
                'No operational recommendations at this time.',
                'Continue monitoring runtime metrics.'
            );
        }

        return $recommendations;
    }

  /**
   * @return array<string, string>
   */
    private function recommendation(string $severity, string $title, string $description, string $action): array
    {
        return [
            'severity' => $severity,
            'severity_color' => ApplicationRuntimeHelper::statusColor($severity),
            'title' => $title,
            'description' => $description,
            'action' => $action,
        ];
    }
}
