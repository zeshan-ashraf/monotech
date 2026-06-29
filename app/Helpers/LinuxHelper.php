<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Safe wrapper for read-only Linux command execution.
 */
class LinuxHelper
{
    private const LOG_CHANNEL = 'payin';

  /**
   * Execute a command and return trimmed stdout, or null on failure.
   *
   * @param  array<int, string>  $command
   */
    public static function run(array $command, ?int $timeoutSeconds = null): ?string
    {
        if ($command === []) {
            return null;
        }

        $timeout = $timeoutSeconds ?? (int) config('application_runtime.process.ps_timeout_seconds', 3);

        try {
            $process = new Process($command);
            $process->setTimeout(max(1, $timeout));
            $process->run();

            if (! $process->isSuccessful()) {
                Log::channel(self::LOG_CHANNEL)->warning('Linux command failed', [
                    'command' => implode(' ', $command),
                    'exit_code' => $process->getExitCode(),
                    'error' => trim($process->getErrorOutput()),
                ]);

                return null;
            }

            $output = trim($process->getOutput());

            return $output === '' ? null : $output;
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('Linux command exception', [
                'command' => implode(' ', $command),
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch HTTP status body with a short timeout.
     */
    public static function fetchUrl(string $url, int $timeoutSeconds = 2): ?string
    {
        if ($url === '') {
            return null;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => max(1, $timeoutSeconds),
                    'ignore_errors' => true,
                ],
            ]);

            $body = @file_get_contents($url, false, $context);

            if ($body === false || $body === '') {
                return null;
            }

            return $body;
        } catch (Throwable $exception) {
            Log::channel(self::LOG_CHANNEL)->warning('HTTP fetch failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
