<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduleSetting;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        Commands\ArchiveTransactions::class,
        Commands\OldTransaction::class,
        Commands\ArchivePayouts::class,
        Commands\TestingData::class,
        Commands\EasyPaisaCheckTransactionStatus::class,
        \App\Console\Commands\ReportGenerate::class,
        \App\Console\Commands\SurplusAddition::class,
        \App\Console\Commands\RecountReportGenerate::class,
        \App\Console\Commands\AutoFailPendingTransactions::class,
    ];
    protected function schedule(Schedule $schedule): void
    {
        //everyTenSeconds
        $eptime=ScheduleSetting::where('txns_type','easypaisa')->where('value',1)->first();
        $jctime=ScheduleSetting::where('txns_type','jazzcash')->where('value',1)->first();
        if ($eptime) {
            switch ($eptime->type) {
                case 'everyFiveSeconds':
                    $schedule->command('transactions:easypaisa-check-status')->everyFiveSeconds();
                    break;
                case 'everyTenSeconds':
                    $schedule->command('transactions:easypaisa-check-status')->everyTenSeconds();
                    break;
                case 'everyThirtySeconds':
                    $schedule->command('transactions:easypaisa-check-status')->everyThirtySeconds();
                    break;
                case 'everyMinute':
                    $schedule->command('transactions:easypaisa-check-status')->everyMinute();
                    break;
                case 'everyFiveMinutes':
                    $schedule->command('transactions:easypaisa-check-status')->everyFiveMinutes();
                    break;
                case 'everyTenMinutes':
                    $schedule->command('transactions:easypaisa-check-status')->everyTenMinutes();
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$eptime->type}");
            }
        }
        if ($jctime) {
            switch ($jctime->type) {
                case 'everyFiveSeconds':
                    $schedule->command('transactions:jazzcash-check-status')->everyFiveSeconds();
                    break;
                case 'everyTenSeconds':
                    $schedule->command('transactions:jazzcash-check-status')->everyTenSeconds();
                    break;
                case 'everyThirtySeconds':
                    $schedule->command('transactions:jazzcash-check-status')->everyThirtySeconds();
                    break;
                case 'everyMinute':
                    $schedule->command('transactions:jazzcash-check-status')->everyMinute();
                    break;
                case 'everyFiveMinutes':
                    $schedule->command('transactions:jazzcash-check-status')->everyFiveMinutes();
                    break;
                case 'everyTenMinutes':
                    $schedule->command('transactions:jazzcash-check-status')->everyTenMinutes();
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$jctime->type}");
            }
        }
        $schedule->command('transactions:jazzcash-recheck-status')->everyMinute();
        $schedule->command('report:generate')->everyMinute();
        $schedule->command('transactions:archive')->dailyAt('02:00');
        $schedule->command('transactions:backup')->dailyAt('02:30');
        $schedule->command('payouts:archive')->daily('03:00');
        $schedule->command('app:recount-report-generate')->dailyAt('01:00');
        $schedule->command('transactions:auto-fail')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
