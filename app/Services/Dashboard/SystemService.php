<?php

namespace App\Services\Dashboard;

use App\Helpers\SystemHelper;

class SystemService
{
  /**
   * Collect live server information for the OPS dashboard.
   *
   * @return array{
   *     health: string,
   *     health_color: string,
   *     hostname: string,
   *     os: string,
   *     uptime: string,
   *     ip_address: string,
   *     kernel: string,
   *     cpu: array{usage: string, cores: string},
   *     ram: array{used: string, total: string, percentage: string},
   *     disk: array{used: string, total: string, percentage: string},
   *     load_average: array{current: string, 1m: string, 5m: string, 15m: string},
   *     network: array{total: string, download: string, upload: string}
   * }
   */
    public function serverInfo(): array
    {
        $cpuUsage = SystemHelper::getCpuUsage();
        $cpuCores = SystemHelper::getCpuCores();
        $ram = SystemHelper::getRamUsage();
        $disk = SystemHelper::getDiskUsage();
        $load = SystemHelper::getLoadAverage();
        $network = SystemHelper::getNetworkUsage();

        $health = $this->resolveHealth($cpuUsage, $ram['percentage'], $disk['percentage']);

        return [
            'health' => $health['status'],
            'health_color' => $health['color'],
            'hostname' => SystemHelper::getHostname(),
            'os' => SystemHelper::getOperatingSystem(),
            'uptime' => SystemHelper::getUptime(),
            'ip_address' => SystemHelper::getServerIp(),
            'kernel' => SystemHelper::getKernelVersion(),
            'cpu' => [
                'usage' => $this->formatPercent($cpuUsage),
                'cores' => $this->formatCpuCores($cpuUsage, $cpuCores),
            ],
            'ram' => [
                'used' => $this->formatGigabytes($ram['used']),
                'total' => $this->formatGigabytes($ram['total']),
                'percentage' => $this->formatPercent($ram['percentage']),
            ],
            'disk' => [
                'used' => $this->formatGigabytes($disk['used']),
                'total' => $this->formatGigabytes($disk['total']),
                'percentage' => $this->formatPercent($disk['percentage']),
            ],
            'load_average' => [
                'current' => $this->formatLoad($load['current']),
                '1m' => $this->formatLoad($load['1m']),
                '5m' => $this->formatLoad($load['5m']),
                '15m' => $this->formatLoad($load['15m']),
            ],
            'network' => [
                'total' => $this->formatMegabytesPerSecond($network['total']),
                'download' => $this->formatMegabytesPerSecond($network['download']),
                'upload' => $this->formatMegabytesPerSecond($network['upload']),
            ],
        ];
    }

  /**
   * @return array{status: string, color: string}
   */
    private function resolveHealth(float $cpu, float $ram, float $disk): array
    {
        if ($cpu >= 90 || $ram >= 90 || $disk >= 95) {
            return ['status' => 'Critical', 'color' => 'danger'];
        }

        if ($cpu >= 70 || $ram >= 80 || $disk >= 85) {
            return ['status' => 'Warning', 'color' => 'warning'];
        }

        return ['status' => 'Healthy', 'color' => 'success'];
    }

    private function formatCpuCores(float $usagePercent, int $totalCores): string
    {
        $usedCores = round(($usagePercent / 100) * $totalCores, 1);

        return sprintf('%s / %d Cores', rtrim(rtrim(number_format($usedCores, 1, '.', ''), '0'), '.'), $totalCores);
    }

    private function formatPercent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . '%';
    }

    private function formatGigabytes(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . ' GB';
    }

    private function formatLoad(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatMegabytesPerSecond(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . ' MB/s';
    }

  /**
   * Build overview metric cards from live server info for the top dashboard row.
   *
   * @param  array<string, mixed>  $info
   * @return array<int, array<string, mixed>>
   */
    public function overviewCards(array $info): array
    {
        $load = $info['load_average'];
        $network = $info['network'];

        return [
            [
                'key' => 'cpu',
                'title' => 'CPU Usage',
                'value' => $info['cpu']['usage'],
                'subtitle' => $info['cpu']['cores'],
                'icon' => 'fa-microchip',
                'color' => 'primary',
                'sparkline' => $this->placeholderSparkline(12, 5),
            ],
            [
                'key' => 'ram',
                'title' => 'RAM Usage',
                'value' => $info['ram']['used'],
                'subtitle' => $info['ram']['percentage'] . ' of ' . $info['ram']['total'],
                'icon' => 'fa-server',
                'color' => 'success',
                'sparkline' => $this->placeholderSparkline(12, 8),
            ],
            [
                'key' => 'disk',
                'title' => 'Disk Usage',
                'value' => $info['disk']['used'],
                'subtitle' => $info['disk']['percentage'] . ' of ' . $info['disk']['total'],
                'icon' => 'fa-hdd-o',
                'color' => 'info',
                'sparkline' => $this->placeholderSparkline(12, 3),
            ],
            [
                'key' => 'load',
                'title' => 'Load Average',
                'value' => $load['current'],
                'subtitle' => sprintf('1m: %s  5m: %s  15m: %s', $load['1m'], $load['5m'], $load['15m']),
                'icon' => 'fa-tachometer',
                'color' => 'warning',
                'sparkline' => $this->placeholderSparkline(12, 2),
            ],
            [
                'key' => 'network',
                'title' => 'Network I/O',
                'value' => $network['total'],
                'subtitle' => '↓ ' . $network['download'] . '  ↑ ' . $network['upload'],
                'icon' => 'fa-exchange',
                'color' => 'secondary',
                'sparkline' => $this->placeholderSparkline(12, 4),
            ],
        ];
    }

  /**
   * Decorative sparkline until historical metrics are available.
   *
   * @return array<int, int>
   */
    private function placeholderSparkline(int $points, int $seed): array
    {
        $series = [];
        $value = 15 + ($seed * 4);

        for ($i = 0; $i < $points; $i++) {
            $value = max(1, $value + (($i + $seed) % 5) - 2);
            $series[] = $value;
        }

        return $series;
    }
}
