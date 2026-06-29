<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\ApplicationRuntimeMonitor;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobRetrying;

class ApplicationRuntimeServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ScheduledTaskStarting::class => [
            [ApplicationRuntimeMonitor::class, 'handleScheduledTaskStarting'],
        ],
        ScheduledTaskFinished::class => [
            [ApplicationRuntimeMonitor::class, 'handleScheduledTaskFinished'],
        ],
        ScheduledTaskFailed::class => [
            [ApplicationRuntimeMonitor::class, 'handleScheduledTaskFailed'],
        ],
        JobProcessing::class => [
            [ApplicationRuntimeMonitor::class, 'handleJobProcessing'],
        ],
        JobProcessed::class => [
            [ApplicationRuntimeMonitor::class, 'handleJobProcessed'],
        ],
        JobFailed::class => [
            [ApplicationRuntimeMonitor::class, 'handleJobFailed'],
        ],
        JobRetrying::class => [
            [ApplicationRuntimeMonitor::class, 'handleJobRetrying'],
        ],
    ];
}
