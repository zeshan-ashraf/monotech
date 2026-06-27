<?php

namespace App\Console\Commands;

use App\Services\Logging\LogPermissionsRepairService;
use Illuminate\Console\Command;

/**
 * Verifies and repairs ownership and permissions of today's daily log files.
 */
class RepairLogPermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'logs:repair-permissions';

    /**
     * @var string
     */
    protected $description = 'Repair ownership and permissions of today\'s log files';

    public function handle(LogPermissionsRepairService $repairService): int
    {
        $repairService->repairTodaysLogFiles();

        return self::SUCCESS;
    }
}
