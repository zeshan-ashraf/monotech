<?php

namespace App\Helpers;

/**
 * Low-level system metrics via native PHP and Linux /proc filesystem.
 * All methods are static; no shell access.
 */
class SystemHelper
{
    private const PROC_STAT = '/proc/stat';

    private const PROC_MEMINFO = '/proc/meminfo';

    private const PROC_NET_DEV = '/proc/net/dev';

    private const PROC_UPTIME = '/proc/uptime';

    private const OS_RELEASE = '/etc/os-release';

  /**
   * @return non-empty-string
   */
    public static function getHostname(): string
    {
        $hostname = gethostname();

        return $hostname !== false && $hostname !== '' ? $hostname : 'N/A';
    }

  /**
   * @return non-empty-string
   */
    public static function getOperatingSystem(): string
    {
        $release = self::readFile(self::OS_RELEASE);

        if ($release !== null && preg_match('/PRETTY_NAME="([^"]+)"/', $release, $matches)) {
            return $matches[1];
        }

        return trim(php_uname('s') . ' ' . php_uname('r'));
    }

  /**
   * @return non-empty-string
   */
    public static function getKernelVersion(): string
    {
        $kernel = php_uname('r');

        return $kernel !== '' ? $kernel : 'N/A';
    }

  /**
   * @return non-empty-string
   */
    public static function getServerIp(): string
    {
        if (function_exists('net_get_interfaces')) {
            foreach (net_get_interfaces() as $name => $config) {
                if (str_starts_with((string) $name, 'lo')) {
                    continue;
                }

                foreach ($config['unicast'] ?? [] as $address) {
                    $ip = $address['address'] ?? '';

                    if (
                        ($address['family'] ?? null) === AF_INET
                        && $ip !== ''
                        && ! str_starts_with($ip, '127.')
                    ) {
                        return $ip;
                    }
                }
            }
        }

        $serverAddr = $_SERVER['SERVER_ADDR'] ?? null;

        if (is_string($serverAddr) && $serverAddr !== '' && ! str_starts_with($serverAddr, '127.')) {
            return $serverAddr;
        }

        $resolved = gethostbyname(self::getHostname());

        if ($resolved !== '' && $resolved !== self::getHostname()) {
            return $resolved;
        }

        return 'N/A';
    }

  /**
   * CPU usage percentage (0–100).
   */
    public static function getCpuUsage(): float
    {
        $first = self::readCpuTimes();

        if ($first === null) {
            return 0.0;
        }

        usleep(100_000);

        $second = self::readCpuTimes();

        if ($second === null) {
            return 0.0;
        }

        $totalDiff = array_sum($second) - array_sum($first);
        $idleDiff = $second['idle'] - $first['idle'];

        if ($totalDiff <= 0) {
            return 0.0;
        }

        return round((1 - ($idleDiff / $totalDiff)) * 100, 1);
    }

    public static function getCpuCores(): int
    {
        $cpuInfo = self::readFile('/proc/cpuinfo');

        if ($cpuInfo !== null) {
            preg_match_all('/^processor\s*:/m', $cpuInfo, $matches);
            $count = count($matches[0]);

            if ($count > 0) {
                return $count;
            }
        }

        return 1;
    }

  /**
   * @return array{used: float, total: float, percentage: float}
   */
    public static function getRamUsage(): array
    {
        $memInfo = self::parseMemInfo();

        if ($memInfo === null) {
            return ['used' => 0.0, 'total' => 0.0, 'percentage' => 0.0];
        }

        $totalKb = $memInfo['MemTotal'] ?? 0;
        $availableKb = $memInfo['MemAvailable'] ?? ($memInfo['MemFree'] ?? 0);
        $usedKb = max(0, $totalKb - $availableKb);

        $totalGb = self::kibToGib($totalKb);
        $usedGb = self::kibToGib($usedKb);
        $percentage = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0.0;

        return [
            'used' => $usedGb,
            'total' => $totalGb,
            'percentage' => $percentage,
        ];
    }

  /**
   * @return array{used: float, total: float, percentage: float}
   */
    public static function getDiskUsage(string $path = '/'): array
    {
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false) {
            return ['used' => 0.0, 'total' => 0.0, 'percentage' => 0.0];
        }

        $used = $total - $free;
        $totalGb = round($total / 1024 / 1024 / 1024, 1);
        $usedGb = round($used / 1024 / 1024 / 1024, 1);
        $percentage = $total > 0 ? round(($used / $total) * 100, 1) : 0.0;

        return [
            'used' => $usedGb,
            'total' => $totalGb,
            'percentage' => $percentage,
        ];
    }

  /**
   * @return array{current: float, 1m: float, 5m: float, 15m: float}
   */
    public static function getLoadAverage(): array
    {
        $load = sys_getloadavg();

        if ($load === false) {
            return ['current' => 0.0, '1m' => 0.0, '5m' => 0.0, '15m' => 0.0];
        }

        return [
            'current' => round($load[0], 2),
            '1m' => round($load[0], 2),
            '5m' => round($load[1], 2),
            '15m' => round($load[2], 2),
        ];
    }

  /**
   * Network throughput in MB/s (sampled over ~500 ms).
   *
   * @return array{total: float, download: float, upload: float}
   */
    public static function getNetworkUsage(): array
    {
        $first = self::readNetworkBytes();

        if ($first === null) {
            return ['total' => 0.0, 'download' => 0.0, 'upload' => 0.0];
        }

        usleep(500_000);

        $second = self::readNetworkBytes();

        if ($second === null) {
            return ['total' => 0.0, 'download' => 0.0, 'upload' => 0.0];
        }

        $intervalSeconds = 0.5;
        $download = self::bytesToMegabytesPerSecond($second['rx'] - $first['rx'], $intervalSeconds);
        $upload = self::bytesToMegabytesPerSecond($second['tx'] - $first['tx'], $intervalSeconds);

        return [
            'download' => $download,
            'upload' => $upload,
            'total' => round($download + $upload, 1),
        ];
    }

  /**
   * @return non-empty-string
   */
    public static function getUptime(): string
    {
        $uptime = self::readFile(self::PROC_UPTIME);

        if ($uptime === null) {
            return 'N/A';
        }

        $seconds = (int) floor((float) strtok($uptime, ' '));

        return self::formatDuration($seconds);
    }

  /**
   * @return array{idle: int, user: int, nice: int, system: int}|null
   */
    private static function readCpuTimes(): ?array
    {
        $stat = self::readFile(self::PROC_STAT);

        if ($stat === null) {
            return null;
        }

        $line = strtok($stat, "\n");

        if ($line === false || ! str_starts_with($line, 'cpu ')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($line));
        array_shift($parts);

        if (count($parts) < 4) {
            return null;
        }

        return [
            'user' => (int) $parts[0],
            'nice' => (int) $parts[1],
            'system' => (int) $parts[2],
            'idle' => (int) $parts[3],
        ];
    }

  /**
   * @return array<string, int>|null
   */
    private static function parseMemInfo(): ?array
    {
        $content = self::readFile(self::PROC_MEMINFO);

        if ($content === null) {
            return null;
        }

        $info = [];

        foreach (explode("\n", $content) as $line) {
            if (preg_match('/^([A-Za-z]+):\s+(\d+)\s+kB$/', $line, $matches)) {
                $info[$matches[1]] = (int) $matches[2];
            }
        }

        return $info !== [] ? $info : null;
    }

  /**
   * @return array{rx: int, tx: int}|null
   */
    private static function readNetworkBytes(): ?array
    {
        $content = self::readFile(self::PROC_NET_DEV);

        if ($content === null) {
            return null;
        }

        $rx = 0;
        $tx = 0;

        foreach (explode("\n", $content) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$interface, $data] = explode(':', $line, 2);
            $interface = trim($interface);

            if ($interface === '' || str_starts_with($interface, 'lo')) {
                continue;
            }

            $parts = preg_split('/\s+/', trim($data));

            if (count($parts) < 9) {
                continue;
            }

            $rx += (int) $parts[0];
            $tx += (int) $parts[8];
        }

        return ['rx' => $rx, 'tx' => $tx];
    }

    private static function readFile(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }

    private static function kibToGib(int $kib): float
    {
        return round($kib / 1024 / 1024, 1);
    }

    private static function bytesToMegabytesPerSecond(int $bytes, float $seconds): float
    {
        if ($seconds <= 0) {
            return 0.0;
        }

        return round(($bytes / $seconds) / 1024 / 1024, 1);
    }

  /**
   * @return non-empty-string
   */
    private static function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86_400);
        $seconds %= 86_400;
        $hours = intdiv($seconds, 3_600);
        $seconds %= 3_600;
        $minutes = intdiv($seconds, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }

        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }

        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }
}
