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
        // Get schedule settings from database
        $eptime = ScheduleSetting::where('txns_type', 'easypaisa')->where('value', 1)->first();
        $jctime = ScheduleSetting::where('txns_type', 'jazzcash')->where('value', 1)->first();
        
        // Schedule EasyPaisa check with only one instance based on setting
        if ($eptime) {
            switch ($eptime->type) {
                case 'everyFiveSeconds':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyFiveSeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyTenSeconds':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyTenSeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyThirtySeconds':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyThirtySeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyMinute':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyMinute()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyFiveMinutes':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyFiveMinutes()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyTenMinutes':
                    $schedule->command('transactions:easypaisa-check-status')
                        ->everyTenMinutes()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$eptime->type}");
            }
        }
        
        // Schedule JazzCash check with only one instance based on setting
        if ($jctime) {
            switch ($jctime->type) {
                case 'everyFiveSeconds':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyFiveSeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyTenSeconds':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyTenSeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyThirtySeconds':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyThirtySeconds()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyMinute':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyMinute()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyFiveMinutes':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyFiveMinutes()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                case 'everyTenMinutes':
                    $schedule->command('transactions:jazzcash-check-status')
                        ->everyTenMinutes()
                        ->withoutOverlapping()
                        ->runInBackground();
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$jctime->type}");
            }
        }
        
        // Other scheduled commands
        $schedule->command('transactions:jazzcash-recheck-status')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
            
        $schedule->command('report:generate')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
            
        $schedule->command('transactions:archive')
            ->dailyAt('12:15')
            ->withoutOverlapping();
            
        $schedule->command('transactions:backup')
            ->dailyAt('12:30')
            ->withoutOverlapping();
            
        $schedule->command('payouts:archive')
            ->daily('12:45')
            ->withoutOverlapping();
            
        $schedule->command('app:recount-report-generate')
            ->dailyAt('01:00')
            ->withoutOverlapping();
            
        $schedule->command('transactions:auto-fail')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
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
