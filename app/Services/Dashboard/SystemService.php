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
}
