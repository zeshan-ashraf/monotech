<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Helpers\ApiTrafficHelper;

/**
 * Reads API traffic metrics from Redis and returns dashboard-ready payloads.
 */
class TrafficDashboardService
{
    public function __construct(
        private readonly ApiTrafficService $apiTrafficService
    ) {
    }

    /**
     * Aggregate traffic for the selected window (minutes).
     *
     * @param  list<string>|null  $apiTypes
     * @return array{
     *     window_minutes: int,
     *     window_seconds: int,
     *     incoming_requests: int,
     *     accepted_requests: int,
     *     rejected_requests: int,
     *     gateway_requests: int,
     *     completed_requests: int,
     *     success_count: int,
     *     failed_count: int,
     *     pending_count: int,
     *     tps: float,
     *     tpm: float,
     *     success_rate: float,
     *     average_response_time: float,
     *     maximum_response_time: int,
     *     gateway_errors: int,
     *     application_errors: int,
     *     infrastructure_errors: int,
     *     timeouts: int,
     *     slow_requests: int,
     *     very_slow_requests: int,
     *     sparkline: list<int>,
     *     by_api: array<string, array<string, int|float>>
     * }
     */
    public function getTraffic(int $minutes = 5, ?array $apiTypes = null): array
    {
        $minutes = ApiTrafficHelper::normalizeWindowMinutes($minutes);
        $windowSeconds = $minutes * 60;
        $totals = $this->apiTrafficService->aggregateWindowMetrics($apiTypes, $minutes);

        $incoming = (int) ($totals[ApiTrafficHelper::FIELD_INCOMING] ?? 0);
        $accepted = (int) ($totals[ApiTrafficHelper::FIELD_ACCEPTED] ?? 0);
        $success = (int) ($totals[ApiTrafficHelper::FIELD_SUCCESS] ?? 0);
        $responseSamples = (int) ($totals[ApiTrafficHelper::FIELD_RESPONSE_SAMPLES] ?? 0);
        $totalResponseTime = (int) ($totals[ApiTrafficHelper::FIELD_TOTAL_RESPONSE_TIME] ?? 0);

        $payload = [
            'window_minutes' => $minutes,
            'window_seconds' => $windowSeconds,
            'incoming_requests' => $incoming,
            'accepted_requests' => $accepted,
            'rejected_requests' => (int) ($totals[ApiTrafficHelper::FIELD_REJECTED] ?? 0),
            'gateway_requests' => (int) ($totals[ApiTrafficHelper::FIELD_GATEWAY_CALLS] ?? 0),
            'completed_requests' => (int) ($totals[ApiTrafficHelper::FIELD_COMPLETED] ?? 0),
            'success_count' => $success,
            'failed_count' => (int) ($totals[ApiTrafficHelper::FIELD_FAILED] ?? 0),
            'pending_count' => (int) ($totals[ApiTrafficHelper::FIELD_PENDING] ?? 0),
            'tps' => $this->calculateRate($incoming, $windowSeconds),
            'tpm' => $this->calculateRate($incoming, $minutes),
            'success_rate' => $this->calculateSuccessRate($success, $incoming),
            'average_response_time' => $responseSamples > 0
                ? round($totalResponseTime / $responseSamples, 2)
                : 0.0,
            'maximum_response_time' => (int) ($totals[ApiTrafficHelper::FIELD_MAX_RESPONSE_TIME] ?? 0),
            'gateway_errors' => (int) ($totals[ApiTrafficHelper::FIELD_GATEWAY_ERRORS] ?? 0),
            'application_errors' => (int) ($totals[ApiTrafficHelper::FIELD_APPLICATION_ERRORS] ?? 0),
            'infrastructure_errors' => (int) ($totals[ApiTrafficHelper::FIELD_INFRASTRUCTURE_ERRORS] ?? 0),
            'timeouts' => (int) ($totals[ApiTrafficHelper::FIELD_TIMEOUTS] ?? 0),
            'slow_requests' => (int) ($totals[ApiTrafficHelper::FIELD_SLOW_REQUESTS] ?? 0),
            'very_slow_requests' => (int) ($totals[ApiTrafficHelper::FIELD_VERY_SLOW_REQUESTS] ?? 0),
            'sparkline' => $this->apiTrafficService->incomingSparkline($apiTypes, $minutes),
            'by_api' => $this->buildPerApiBreakdown($minutes, $apiTypes),
        ];

        return $payload;
    }

    /**
     * @param  list<string>|null  $apiTypes
     * @return array<string, array<string, int|float>>
     */
    private function buildPerApiBreakdown(int $minutes, ?array $apiTypes): array
    {
        $apiTypes = $apiTypes ?? ApiTrafficHelper::apiTypes();
        $breakdown = [];

        foreach ($apiTypes as $apiType) {
            $totals = $this->apiTrafficService->aggregateWindowMetrics([$apiType], $minutes);
            $incoming = (int) ($totals[ApiTrafficHelper::FIELD_INCOMING] ?? 0);
            $success = (int) ($totals[ApiTrafficHelper::FIELD_SUCCESS] ?? 0);
            $windowSeconds = $minutes * 60;

            $breakdown[$apiType] = [
                'incoming_requests' => $incoming,
                'accepted_requests' => (int) ($totals[ApiTrafficHelper::FIELD_ACCEPTED] ?? 0),
                'rejected_requests' => (int) ($totals[ApiTrafficHelper::FIELD_REJECTED] ?? 0),
                'gateway_requests' => (int) ($totals[ApiTrafficHelper::FIELD_GATEWAY_CALLS] ?? 0),
                'success_count' => $success,
                'tps' => $this->calculateRate($incoming, $windowSeconds),
                'tpm' => $this->calculateRate($incoming, $minutes),
                'success_rate' => $this->calculateSuccessRate($success, $incoming),
            ];
        }

        return $breakdown;
    }

    private function calculateRate(int $count, int $divisor): float
    {
        if ($divisor <= 0) {
            return 0.0;
        }

        return round($count / $divisor, 4);
    }

    private function calculateSuccessRate(int $successCount, int $incomingCount): float
    {
        if ($incomingCount <= 0) {
            return 0.0;
        }

        return round(($successCount / $incomingCount) * 100, 2);
    }

    /**
     * Dashboard-ready payload for the OPS traffic panel (initial render + AJAX).
     *
     * @return array<string, mixed>
     */
    public function dashboardPayload(int $minutes = 5): array
    {
        $metrics = $this->getTraffic($minutes);
        $minutes = $metrics['window_minutes'];

        return [
            'metrics' => $metrics,
            'window_minutes' => $minutes,
            'windows' => ApiTrafficHelper::allowedWindows(),
            'cards' => $this->buildStatCards($metrics),
            'api_rows' => $this->buildApiRows($metrics['by_api'] ?? []),
            'errors' => $this->buildErrorBadges($metrics),
            'chart' => [
                'labels' => $this->buildChartLabels($minutes),
                'series' => $metrics['sparkline'] ?? [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildStatCards(array $metrics): array
    {
        $avgMs = (float) ($metrics['average_response_time'] ?? 0);

        return [
            [
                'key' => 'incoming',
                'label' => 'Incoming Requests',
                'value' => (int) ($metrics['incoming_requests'] ?? 0),
                'color' => 'primary',
                'chart' => true,
            ],
            [
                'key' => 'tps',
                'label' => 'TPS',
                'value' => number_format((float) ($metrics['tps'] ?? 0), 2),
                'color' => 'info',
                'chart' => false,
            ],
            [
                'key' => 'tpm',
                'label' => 'TPM',
                'value' => number_format((float) ($metrics['tpm'] ?? 0), 1),
                'color' => 'secondary',
                'chart' => false,
            ],
            [
                'key' => 'success_rate',
                'label' => 'Success Rate',
                'value' => number_format((float) ($metrics['success_rate'] ?? 0), 1) . '%',
                'color' => 'success',
                'chart' => false,
            ],
            [
                'key' => 'avg_response',
                'label' => 'Avg Response',
                'value' => $avgMs > 0
                    ? ApiTrafficHelper::formatDurationSeconds($avgMs)
                    : '0.00 sec',
                'color' => 'warning',
                'chart' => false,
            ],
            [
                'key' => 'rejected',
                'label' => 'Rejected',
                'value' => (int) ($metrics['rejected_requests'] ?? 0),
                'color' => 'danger',
                'chart' => false,
            ],
        ];
    }

    /**
     * @param  array<string, array<string, int|float>>  $byApi
     * @return array<int, array<string, mixed>>
     */
    private function buildApiRows(array $byApi): array
    {
        $labels = config('api_traffic.api_labels', []);
        $rows = [];

        foreach ($byApi as $apiType => $stats) {
            $incoming = (int) ($stats['incoming_requests'] ?? 0);

            $rows[] = [
                'key' => $apiType,
                'label' => (string) ($labels[$apiType] ?? ucfirst(str_replace('_', ' ', $apiType))),
                'incoming' => $incoming,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['incoming'] <=> $a['incoming']);

        $maxIncoming = max(1, ...array_column($rows, 'incoming'));

        foreach ($rows as &$row) {
            $row['percent'] = round(($row['incoming'] / $maxIncoming) * 100, 1);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildErrorBadges(array $metrics): array
    {
        return [
            ['key' => 'gateway_errors', 'label' => 'Gateway Errors', 'value' => (int) ($metrics['gateway_errors'] ?? 0), 'color' => 'danger'],
            ['key' => 'application_errors', 'label' => 'Application Errors', 'value' => (int) ($metrics['application_errors'] ?? 0), 'color' => 'warning'],
            ['key' => 'infrastructure_errors', 'label' => 'Infrastructure', 'value' => (int) ($metrics['infrastructure_errors'] ?? 0), 'color' => 'info'],
            ['key' => 'timeouts', 'label' => 'Timeouts', 'value' => (int) ($metrics['timeouts'] ?? 0), 'color' => 'secondary'],
            ['key' => 'slow_requests', 'label' => 'Slow Requests', 'value' => (int) ($metrics['slow_requests'] ?? 0), 'color' => 'primary'],
        ];
    }

    /**
     * @return list<string>
     */
    private function buildChartLabels(int $minutes): array
    {
        $labels = [];

        for ($index = $minutes - 1; $index >= 0; $index--) {
            if ($index === 0) {
                $labels[] = 'Now';

                continue;
            }

            $labels[] = '-' . $index . 'm';
        }

        return $labels;
    }
}
