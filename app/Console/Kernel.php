<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduleSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $logSchedule = function($command, $event, $runId = null) {
            $now = now()->format('Y-m-d H:i:s');
            $msg = "[$now] RunID: $runId | Command: $command | $event";
            Log::channel('schedule_debug')->info($msg);
        };
        $logScheduleEnd = function($command, $start, $runId = null) {
            $end = microtime(true);
            $duration = number_format($end - $start, 2);
            $memory = number_format(memory_get_peak_usage(true) / 1024 / 1024, 2);
            $now = now()->format('Y-m-d H:i:s');
            $msg = "[$now] RunID: $runId | Command: $command | Duration: {$duration}s | Memory: {$memory} MB";
            Log::channel('schedule_debug')->info($msg);
        };
        $wrapSchedule = function($event, $command) use ($logSchedule, $logScheduleEnd) {
            $start = null;
            $runId = null;
            $event->before(function () use (&$start, &$runId, $command, $logSchedule) {
                $start = microtime(true);
                $runId = (string) Str::uuid();
                $logSchedule($command, 'start', $runId);
            });
            $event->after(function () use (&$start, &$runId, $command, $logScheduleEnd) {
                $logScheduleEnd($command, $start, $runId);
            });
            //$event->appendOutputTo(storage_path('logs/schedule_' . str_replace(':', '_', $command) . '.log'));
        };
        $eptime=ScheduleSetting::where('txns_type','easypaisa')->where('value',1)->first();
        $jctime=ScheduleSetting::where('txns_type','jazzcash')->where('value',1)->first();
        if ($eptime) {
            switch ($eptime->type) {
                case 'everyFiveSeconds':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyFiveSeconds();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                case 'everyTenSeconds':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyTenSeconds();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                case 'everyThirtySeconds':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyThirtySeconds();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                case 'everyMinute':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyMinute();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                case 'everyFiveMinutes':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyFiveMinutes();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                case 'everyTenMinutes':
                    $event = $schedule->command('transactions:easypaisa-check-status')->everyTenMinutes();
                    $wrapSchedule($event, 'transactions:easypaisa-check-status');
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$eptime->type}");
            }
        }
        if ($jctime) {
            switch ($jctime->type) {
                case 'everyFiveSeconds':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyFiveSeconds();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                case 'everyTenSeconds':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyTenSeconds();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                case 'everyThirtySeconds':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyThirtySeconds();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                case 'everyMinute':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyMinute();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                case 'everyFiveMinutes':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyFiveMinutes();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                case 'everyTenMinutes':
                    $event = $schedule->command('transactions:jazzcash-check-status')->everyTenMinutes();
                    $wrapSchedule($event, 'transactions:jazzcash-check-status');
                    break;
                default:
                    throw new \Exception("Invalid schedule type: {$jctime->type}");
            }
        }
        /*
        $schedule->command('transactions:jazzcash-recheck-status')->everyMinute();
        $schedule->command('report:generate')->everyMinute();
        $schedule->command('transactions:archive')->dailyAt('02:00');
        $schedule->command('transactions:backup')->dailyAt('02:30');
        $schedule->command('payouts:archive')->daily('03:00');
        $schedule->command('app:recount-report-generate')->dailyAt('01:00');
        $schedule->command('transactions:auto-fail')->everyFiveMinutes();
        */
        $event = $schedule->command('transactions:jazzcash-recheck-status')->everyMinute();
        $wrapSchedule($event, 'transactions:jazzcash-recheck-status');
        $event = $schedule->command('report:generate')->everyMinute();
        $wrapSchedule($event, 'report:generate');
        // $schedule->command('suplus:addition')->everyThirtySeconds();
        $event = $schedule->command('transactions:archive')->dailyAt('02:00');
        $wrapSchedule($event, 'transactions:archive');
        $event = $schedule->command('transactions:backup')->dailyAt('02:30');
        $wrapSchedule($event, 'transactions:backup');
        $event = $schedule->command('payouts:archive')->daily('03:00');
        $wrapSchedule($event, 'payouts:archive');
        // $schedule->command('transactions:old')->dailyAt('04:25');
        $event = $schedule->command('app:recount-report-generate')->dailyAt('01:00');
        $wrapSchedule($event, 'app:recount-report-generate');
        $event = $schedule->command('transactions:auto-fail')->everyFiveMinutes();
        $wrapSchedule($event, 'transactions:auto-fail');

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
