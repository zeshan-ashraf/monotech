<?php

namespace App\Services\Logging;

use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Verifies and repairs ownership and permissions of today's daily log files.
 */
class LogPermissionsRepairService
{
    private readonly string $directory;

    private readonly string $ownerName;

    private readonly string $groupName;

    private readonly int $expectedPermissions;

    /** @var list<string> */
    private readonly array $dailyLogBasenames;

    private readonly string $dateFormat;

    private ?int $expectedOwnerUid = null;

    private ?int $expectedGroupGid = null;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->directory = (string) config('log_permissions.directory');
        $this->ownerName = (string) config('log_permissions.owner');
        $this->groupName = (string) config('log_permissions.group');
        $this->expectedPermissions = (int) config('log_permissions.permissions', 0644);
        $this->dailyLogBasenames = (array) config('log_permissions.daily_log_basenames', []);
        $this->dateFormat = (string) config('log_permissions.date_format', 'Y-m-d');
    }

    /**
     * Repair today's log files when ownership or permissions are incorrect.
     */
    public function repairTodaysLogFiles(?Carbon $date = null): void
    {
        if (! $this->posixIsAvailable()) {
            return;
        }

        try {
            $this->resolveExpectedOwnership();
        } catch (Throwable $exception) {
            $this->logger->error(
                'Unable to resolve log file ownership for repair',
                ['exception' => $exception],
            );

            return;
        }

        foreach ($this->buildTodaysLogFilePaths($date) as $filePath) {
            if (! file_exists($filePath)) {
                continue;
            }

            $this->repairFileIfNeeded($filePath);
        }
    }

    /**
     * @return list<string> Absolute paths for today's expected daily log files.
     */
    public function buildTodaysLogFilePaths(?Carbon $date = null): array
    {
        $dateSuffix = ($date ?? now())->format($this->dateFormat);

        return array_map(
            fn (string $basename): string => $this->directory.DIRECTORY_SEPARATOR.$basename.'-'.$dateSuffix.'.log',
            $this->dailyLogBasenames,
        );
    }

    private function repairFileIfNeeded(string $filePath): void
    {
        $filename = basename($filePath);
        $state = $this->inspectFile($filePath);

        if (! $state['owner_correct'] || ! $state['group_correct']) {
            try {
                $this->repairOwnership($filePath);
                $this->logger->info("Repaired ownership for {$filename}");
            } catch (Throwable $exception) {
                $this->logger->error(
                    "Failed to repair ownership for {$filename}",
                    ['exception' => $exception],
                );
            }
        }

        $state = $this->inspectFile($filePath);

        if (! $state['permissions_correct']) {
            try {
                $this->repairPermissions($filePath);
                $this->logger->info("Repaired permissions for {$filename}");
            } catch (Throwable $exception) {
                $this->logger->error(
                    "Failed to repair permissions for {$filename}",
                    ['exception' => $exception],
                );
            }
        }
    }

    /**
     * Inspect owner, group, writability, and permissions for an existing file.
     *
     * @return array{owner_correct: bool, group_correct: bool, writable: bool, permissions_correct: bool}
     */
    public function inspectFile(string $filePath): array
    {
        $ownerUid = fileowner($filePath);
        $groupGid = filegroup($filePath);
        $writable = is_writable($filePath);
        $permissions = fileperms($filePath);

        return [
            'owner_correct' => $ownerUid !== false && $ownerUid === $this->expectedOwnerUid,
            'group_correct' => $groupGid !== false && $groupGid === $this->expectedGroupGid,
            'writable' => $writable,
            'permissions_correct' => $permissions !== false
                && ($permissions & 0777) === $this->expectedPermissions,
        ];
    }

    private function repairOwnership(string $filePath): void
    {
        if (! chown($filePath, $this->expectedOwnerUid)) {
            throw new \RuntimeException("chown failed for {$filePath}");
        }

        if (! chgrp($filePath, $this->expectedGroupGid)) {
            throw new \RuntimeException("chgrp failed for {$filePath}");
        }
    }

    private function repairPermissions(string $filePath): void
    {
        if (! chmod($filePath, $this->expectedPermissions)) {
            throw new \RuntimeException("chmod failed for {$filePath}");
        }
    }

    private function resolveExpectedOwnership(): void
    {
        if ($this->expectedOwnerUid !== null && $this->expectedGroupGid !== null) {
            return;
        }

        $owner = posix_getpwnam($this->ownerName);
        $group = posix_getgrnam($this->groupName);

        if ($owner === false || $group === false) {
            throw new \RuntimeException(
                "Unable to resolve POSIX user or group: {$this->ownerName}:{$this->groupName}"
            );
        }

        $this->expectedOwnerUid = (int) $owner['uid'];
        $this->expectedGroupGid = (int) $group['gid'];
    }

    private function posixIsAvailable(): bool
    {
        return function_exists('posix_getpwnam')
            && function_exists('posix_getgrnam')
            && function_exists('chown')
            && function_exists('chgrp');
    }
}
