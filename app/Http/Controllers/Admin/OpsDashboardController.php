<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OpsDashboardController extends Controller
{
    /**
     * Display the operations dashboard with static placeholder data (Phase 1).
     */
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'overviewCards' => $this->overviewCards(),
            'phpFpm' => $this->phpFpmStatus(),
            'mysql' => $this->mysqlStatus(),
            'payments' => $this->paymentsOverview(),
            'transactions' => $this->recentTransactions(),
            'paymentStats' => $this->paymentResponseStats(),
            'alerts' => $this->alerts(),
            'serverInfo' => $this->serverInfo(),
            'chartData' => $this->chartData(),
            'refreshIntervals' => ['5s', '10s', '30s', '1m', '5m'],
        ]);
    }

    /**
     * Top-row overview metric cards.
     *
     * @return array<int, array<string, mixed>>
     */
    private function overviewCards(): array
    {
        return [
            [
                'key' => 'cpu',
                'title' => 'CPU Usage',
                'value' => '42%',
                'subtitle' => '4.2 / 8 Cores',
                'icon' => 'fa-microchip',
                'color' => 'primary',
                'sparkline' => [28, 35, 42, 38, 45, 40, 42, 39, 44, 42, 41, 42],
            ],
            [
                'key' => 'ram',
                'title' => 'RAM Usage',
                'value' => '18.6 GB',
                'subtitle' => '58% of 32 GB',
                'icon' => 'fa-server',
                'color' => 'success',
                'sparkline' => [14.2, 15.1, 16.0, 16.8, 17.2, 17.8, 18.0, 18.2, 18.4, 18.5, 18.6, 18.6],
            ],
            [
                'key' => 'disk',
                'title' => 'Disk Usage',
                'value' => '120 GB',
                'subtitle' => '30% of 400 GB',
                'icon' => 'fa-hdd-o',
                'color' => 'info',
                'sparkline' => [115, 116, 117, 118, 118, 119, 119, 120, 120, 120, 120, 120],
            ],
            [
                'key' => 'load',
                'title' => 'Load Average',
                'value' => '3.20',
                'subtitle' => '1m: 3.20  5m: 2.91  15m: 2.45',
                'icon' => 'fa-tachometer',
                'color' => 'warning',
                'sparkline' => [2.1, 2.4, 2.8, 3.0, 3.1, 3.3, 3.2, 3.0, 2.9, 3.1, 3.2, 3.2],
            ],
            [
                'key' => 'network',
                'title' => 'Network I/O',
                'value' => '12.4 MB/s',
                'subtitle' => '↓ 8.7 MB/s  ↑ 3.7 MB/s',
                'icon' => 'fa-exchange',
                'color' => 'secondary',
                'sparkline' => [8.2, 9.1, 10.5, 11.0, 12.1, 11.8, 12.4, 11.9, 12.0, 12.2, 12.3, 12.4],
            ],
        ];
    }

    /**
     * PHP-FPM worker pool status.
     *
     * @return array<string, mixed>
     */
    private function phpFpmStatus(): array
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
     * MySQL server status metrics.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mysqlStatus(): array
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
     * Payment summary counters.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentsOverview(): array
    {
        return [
            ['key' => 'success', 'label' => 'PayIn Success', 'value' => 842, 'color' => 'success', 'sparkline' => [720, 760, 780, 800, 810, 825, 830, 835, 838, 840, 841, 842]],
            ['key' => 'pending', 'label' => 'PayIn Pending', 'value' => 17, 'color' => 'warning', 'sparkline' => [22, 20, 19, 18, 17, 16, 18, 17, 16, 17, 17, 17]],
            ['key' => 'failed', 'label' => 'PayIn Failed', 'value' => 3, 'color' => 'danger', 'sparkline' => [5, 4, 4, 3, 3, 4, 3, 3, 2, 3, 3, 3]],
            ['key' => 'refunds', 'label' => 'Refunds', 'value' => 12, 'color' => 'info', 'sparkline' => [8, 9, 10, 10, 11, 11, 12, 11, 12, 12, 12, 12]],
        ];
    }

    /**
     * Recent payment transactions for the table.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(): array
    {
        return [
            ['id' => 'TXN-982341', 'type' => 'PayIn', 'amount' => '₨ 4,500.00', 'status' => 'success', 'time' => '2m ago', 'response_time' => '0.84s'],
            ['id' => 'TXN-982340', 'type' => 'PayIn', 'amount' => '₨ 12,000.00', 'status' => 'pending', 'time' => '4m ago', 'response_time' => '—'],
            ['id' => 'TXN-982339', 'type' => 'Refund', 'amount' => '₨ 1,200.00', 'status' => 'success', 'time' => '6m ago', 'response_time' => '1.12s'],
            ['id' => 'TXN-982338', 'type' => 'PayIn', 'amount' => '₨ 850.00', 'status' => 'failed', 'time' => '8m ago', 'response_time' => '14.32s'],
            ['id' => 'TXN-982337', 'type' => 'PayIn', 'amount' => '₨ 3,250.00', 'status' => 'success', 'time' => '11m ago', 'response_time' => '0.92s'],
            ['id' => 'TXN-982336', 'type' => 'PayIn', 'amount' => '₨ 7,800.00', 'status' => 'success', 'time' => '14m ago', 'response_time' => '1.05s'],
        ];
    }

    /**
     * Payment API response time footer stats.
     *
     * @return array<string, string>
     */
    private function paymentResponseStats(): array
    {
        return [
            'avg' => '1.24 sec',
            'max' => '14.32 sec',
        ];
    }

    /**
     * System alerts and error events.
     *
     * @return array<int, array<string, string>>
     */
    private function alerts(): array
    {
        return [
            ['severity' => 'danger', 'icon' => 'fa-times-circle', 'title' => 'PayIn Gateway Timeout', 'description' => 'EasyPaisa API response exceeded 15s threshold on node-02', 'time' => '2m ago'],
            ['severity' => 'warning', 'icon' => 'fa-exclamation-triangle', 'title' => 'High PHP-FPM Queue', 'description' => 'Worker pool reached 90% capacity during peak traffic', 'time' => '8m ago'],
            ['severity' => 'info', 'icon' => 'fa-info-circle', 'title' => 'Scheduled Backup Completed', 'description' => 'Daily MySQL dump finished successfully (2.4 GB)', 'time' => '32m ago'],
            ['severity' => 'success', 'icon' => 'fa-check-circle', 'title' => 'SSL Certificate Renewed', 'description' => 'Auto-renewal completed for *.monotech.pk', 'time' => '1h ago'],
            ['severity' => 'warning', 'icon' => 'fa-exclamation-triangle', 'title' => 'Disk Usage Warning', 'description' => '/var/log partition at 78% capacity', 'time' => '2h ago'],
        ];
    }

    /**
     * Server host information shown in the sidebar footer.
     *
     * @return array<string, string>
     */
    private function serverInfo(): array
    {
        return [
            'hostname' => 'monotech-prod',
            'os' => 'Ubuntu 22.04.4 LTS',
            'kernel' => '5.15.0-91-generic',
            'ip' => '103.152.1.45',
            'uptime' => '27d 14h 23m',
            'health' => 'Healthy',
            'health_color' => 'success',
        ];
    }

    /**
     * Chart series data passed to dashboard.js via window.opsDashboardData.
     *
     * @return array<string, mixed>
     */
    private function chartData(): array
    {
        $hours = ['11 PM', '12 AM', '1 AM', '2 AM', '3 AM', '4 AM', '5 AM', '6 AM', '7 AM', '8 AM', '9 AM', '10 AM', '11 AM', '12 PM', '1 PM', '2 PM', '3 PM', '4 PM', '5 PM', '6 PM', '7 PM'];

        return [
            'overview' => collect($this->overviewCards())->mapWithKeys(fn (array $card) => [
                $card['key'] => $card['sparkline'],
            ])->all(),
            'phpfpm' => [
                'workers' => [
                    'busy' => 34,
                    'idle' => 66,
                    'queue' => 0,
                    'max_children' => 0,
                ],
                'requests' => $this->phpFpmStatus()['requests_sparkline'],
                'response' => $this->phpFpmStatus()['response_sparkline'],
                'slow' => $this->phpFpmStatus()['slow_sparkline'],
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
