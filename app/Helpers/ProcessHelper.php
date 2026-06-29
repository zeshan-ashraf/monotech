<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Targeted process inspection helpers (no full process table scans).
 */
class ProcessHelper
{
  /**
   * Count queue worker processes using a narrow pgrep query.
   */
    public static function countQueueWorkers(): ?int
    {
        $workers = LinuxHelper::run(['pgrep', '-fc', 'artisan queue:work']);
        $horizon = LinuxHelper::run(['pgrep', '-fc', 'artisan horizon:work']);

        if ($workers === null && $horizon === null) {
            return null;
        }

        return max(0, (int) $workers) + max(0, (int) $horizon);
    }

  /**
   * Detect whether the Laravel scheduler runner is active.
   */
    public static function isSchedulerRunning(): bool
    {
        $output = LinuxHelper::run(['pgrep', '-fc', 'artisan schedule:run']);

        return $output !== null && (int) $output > 0;
    }

  /**
   * @return array<int, array{pid: int, command: string, elapsed_seconds: int}>
   */
    public static function findLongRunningPhpProcesses(int $thresholdSeconds): array
    {
        $output = LinuxHelper::run([
            'ps',
            '-C',
            'php',
            '-o',
            'pid=,etimes=,args=',
            '--no-headers',
        ]);

        if ($output === null) {
            return [];
        }

        $matches = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '' || ! preg_match('/^(\d+)\s+(\d+)\s+(.+)$/', $line, $parts)) {
                continue;
            }

            $pid = (int) $parts[1];
            $elapsed = (int) $parts[2];
            $command = $parts[3];

            if ($elapsed < $thresholdSeconds) {
                continue;
            }

            if (
                str_contains($command, 'php-fpm')
                || str_contains($command, 'artisan')
            ) {
                $matches[] = [
                    'pid' => $pid,
                    'command' => $command,
                    'elapsed_seconds' => $elapsed,
                ];
            }
        }

        return $matches;
    }

  /**
   * @return array{pid: int|null, elapsed_seconds: int|null}
   */
    public static function findProcessByPattern(string $pattern): array
    {
        $output = LinuxHelper::run(['pgrep', '-af', $pattern]);

        if ($output === null || trim($output) === '') {
            return ['pid' => null, 'elapsed_seconds' => null];
        }

        $line = trim(explode("\n", $output)[0]);

        if (preg_match('/^(\d+)\s+/', $line, $parts)) {
            $pid = (int) $parts[1];
            $elapsedOutput = LinuxHelper::run(['ps', '-p', (string) $pid, '-o', 'etimes=', '--no-headers']);
            $elapsed = is_numeric(trim((string) $elapsedOutput)) ? (int) trim((string) $elapsedOutput) : null;

            return [
                'pid' => $pid,
                'elapsed_seconds' => $elapsed,
            ];
        }

        return ['pid' => null, 'elapsed_seconds' => null];
    }
}
