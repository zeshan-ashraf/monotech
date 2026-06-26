<?php

namespace App\Services\Dashboard;

/**
 * Static placeholder data for OPS dashboard sections not yet wired to live metrics.
 */
class OpsDashboardPlaceholderService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function phpFpmStatus(): array
    {
        return [
            'total_workers' => 100,
            'busy' => 34,
            'idle' => 66,
            'queue' => 0,
            'max_children_hit' => 0,
            'requests_per_sec' => 142.6,
            'avg_response_ms' => 120,
            'slow_requests' => 3,
            'requests_sparkline' => [98, 110, 125, 130, 138, 142, 145, 140, 143, 142, 141, 143],
            'response_sparkline' => [95, 102, 108, 115, 118, 122, 120, 119, 121, 120, 118, 120],
            'slow_sparkline' => [1, 2, 1, 3, 2, 4, 3, 2, 3, 3, 2, 3],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function mysqlStatus(): array
    {
        return [
            ['key' => 'threads_connected', 'label' => 'Threads Connected', 'value' => '85', 'icon' => 'fa-plug', 'color' => 'primary'],
            ['key' => 'threads_running', 'label' => 'Threads Running', 'value' => '6', 'icon' => 'fa-bolt', 'color' => 'warning'],
            ['key' => 'queries_per_sec', 'label' => 'Queries / Sec', 'value' => '250', 'icon' => 'fa-database', 'color' => 'info'],
            ['key' => 'slow_queries', 'label' => 'Slow Queries', 'value' => '0', 'icon' => 'fa-hourglass-half', 'color' => 'danger'],
            ['key' => 'buffer_hit', 'label' => 'Innodb Buffer Hit', 'value' => '99.50%', 'icon' => 'fa-bullseye', 'color' => 'success'],
            ['key' => 'uptime', 'label' => 'Uptime', 'value' => '27d 14h', 'icon' => 'fa-clock-o', 'color' => 'secondary'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paymentsOverview(): array
    {
        return [
            ['key' => 'success', 'label' => 'PayIn Success', 'value' => 842, 'color' => 'success', 'sparkline' => [720, 760, 780, 800, 810, 825, 830, 835, 838, 840, 841, 842]],
            ['key' => 'pending', 'label' => 'PayIn Pending', 'value' => 17, 'color' => 'warning', 'sparkline' => [22, 20, 19, 18, 17, 16, 18, 17, 16, 17, 17, 17]],
            ['key' => 'failed', 'label' => 'PayIn Failed', 'value' => 3, 'color' => 'danger', 'sparkline' => [5, 4, 4, 3, 3, 4, 3, 3, 2, 3, 3, 3]],
            ['key' => 'refunds', 'label' => 'Refunds', 'value' => 12, 'color' => 'info', 'sparkline' => [8, 9, 10, 10, 11, 11, 12, 11, 12, 12, 12, 12]],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentTransactions(): array
    {
        return [
            ['id' => 'TXN85214', 'type' => 'PayIn', 'amount' => '₨ 5,000', 'status' => 'success', 'time' => '2m ago', 'response_time' => '0.84s', 'response_slow' => false],
            ['id' => 'TXN85213', 'type' => 'PayIn', 'amount' => '₨ 12,000', 'status' => 'pending', 'time' => '4m ago', 'response_time' => '—', 'response_slow' => false],
            ['id' => 'TXN85212', 'type' => 'PayIn', 'amount' => '₨ 3,250', 'status' => 'success', 'time' => '6m ago', 'response_time' => '1.05s', 'response_slow' => false],
            ['id' => 'TXN85211', 'type' => 'PayIn', 'amount' => '₨ 850', 'status' => 'failed', 'time' => '8m ago', 'response_time' => '14.32s', 'response_slow' => true],
            ['id' => 'TXN85210', 'type' => 'Refund', 'amount' => '₨ 1,200', 'status' => 'success', 'time' => '11m ago', 'response_time' => '1.12s', 'response_slow' => false],
            ['id' => 'TXN85209', 'type' => 'PayIn', 'amount' => '₨ 7,800', 'status' => 'success', 'time' => '14m ago', 'response_time' => '0.92s', 'response_slow' => false],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function paymentResponseStats(): array
    {
        return [
            'avg' => '1.24 sec',
            'max' => '14.32 sec',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function alerts(): array
    {
        return [
            [
                'severity' => 'danger',
                'icon' => 'fa-times-circle',
                'title' => 'High PHP-FPM Worker Usage',
                'description' => 'Busy workers above 80% — consider scaling the worker pool',
                'time' => '2m ago',
            ],
            [
                'severity' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'title' => 'Slow PayIn API Response',
                'description' => 'Response time above 10s on EasyPaisa gateway',
                'time' => '7m ago',
            ],
            [
                'severity' => 'warning',
                'icon' => 'fa-exclamation-triangle',
                'title' => 'Disk Usage Warning',
                'description' => 'Disk usage above 80% on root partition',
                'time' => '15m ago',
            ],
            [
                'severity' => 'info',
                'icon' => 'fa-info-circle',
                'title' => 'MySQL Connection High',
                'description' => 'Threads connected above 80 — monitor query load',
                'time' => '22m ago',
            ],
            [
                'severity' => 'success',
                'icon' => 'fa-check-circle',
                'title' => 'Backup Completed',
                'description' => 'Database backup finished successfully (2.4 GB)',
                'time' => '1h ago',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function refreshIntervals(): array
    {
        return ['5s', '10s', '30s', '1m', '5m'];
    }

    /**
     * Chart payload for the main dashboard view (overview + payments sparklines).
     *
     * @param  array<int, array<string, mixed>>  $overviewCards
     * @return array<string, mixed>
     */
    public function chartDataForMain(array $overviewCards): array
    {
        return [
            'overview' => collect($overviewCards)->mapWithKeys(fn (array $card) => [
                $card['key'] => $card['sparkline'],
            ])->all(),
            'payments' => collect($this->paymentsOverview())->mapWithKeys(fn (array $item) => [
                $item['key'] => $item['sparkline'],
            ])->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $overviewCards
     * @return array<string, mixed>
     */
    public function chartData(array $overviewCards): array
    {
        $hours = ['11 PM', '12 AM', '1 AM', '2 AM', '3 AM', '4 AM', '5 AM', '6 AM', '7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM'];
        $phpFpm = $this->phpFpmStatus();

        return [
            'overview' => collect($overviewCards)->mapWithKeys(fn (array $card) => [
                $card['key'] => $card['sparkline'],
            ])->all(),
            'phpfpm' => [
                'workers' => [
                    'busy' => $phpFpm['busy'],
                    'idle' => $phpFpm['idle'],
                    'queue' => $phpFpm['queue'],
                    'max_children' => $phpFpm['max_children_hit'],
                ],
                'requests' => $phpFpm['requests_sparkline'],
                'response' => $phpFpm['response_sparkline'],
                'slow' => $phpFpm['slow_sparkline'],
            ],
            'payments' => collect($this->paymentsOverview())->mapWithKeys(fn (array $item) => [
                $item['key'] => $item['sparkline'],
            ])->all(),
            'history' => [
                'labels' => $hours,
                'cpu' => [22, 25, 28, 30, 35, 38, 42, 45, 40, 38, 35, 32, 30, 28, 32, 36, 40, 42, 38, 35, 42],
                'ram' => [14.0, 14.5, 15.0, 15.5, 16.0, 16.5, 17.0, 17.5, 18.0, 18.2, 18.4, 18.5, 18.6, 18.6, 18.5, 18.6, 18.6, 18.6, 18.5, 18.6, 18.6],
                'network_in' => [5.2, 5.8, 6.1, 6.5, 7.0, 7.5, 8.0, 8.5, 8.7, 8.2, 7.8, 7.5, 7.0, 6.8, 7.2, 7.8, 8.2, 8.5, 8.7, 8.5, 8.7],
                'network_out' => [2.1, 2.3, 2.5, 2.8, 3.0, 3.2, 3.4, 3.5, 3.7, 3.5, 3.4, 3.3, 3.2, 3.1, 3.3, 3.5, 3.6, 3.7, 3.7, 3.6, 3.7],
                'mysql_qps' => [180, 195, 210, 220, 235, 240, 250, 245, 230, 220, 215, 225, 240, 250, 245, 235, 230, 240, 250, 245, 250],
            ],
        ];
    }
}
