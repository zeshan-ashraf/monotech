<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Helpers\ApplicationRuntimeHelper;
use App\Services\Dashboard\QueueMonitorService;
use App\Services\Dashboard\SchedulerService;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobRetrying;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApplicationRuntimeMonitor
{
    /** @var array<string, float> */
    private static array $jobStartTimes = [];

    /** @var array<string, float> */
    private static array $schedulerStartTimes = [];

    public function __construct(
        private readonly SchedulerService $schedulerService,
        private readonly QueueMonitorService $queueMonitorService,
    ) {
    }

    public function handleScheduledTaskStarting(ScheduledTaskStarting $event): void
    {
        try {
            $command = $this->scheduledCommandName($event);
            self::$schedulerStartTimes[$command] = microtime(true);
            $this->schedulerService->recordTick();
            $this->schedulerService->recordCommandStart($command, getmypid() ?: null);
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Scheduler start monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleScheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        try {
            $command = $this->scheduledCommandName($event);
            $duration = $this->duration(self::$schedulerStartTimes, $command);
            $status = $duration >= (int) config('application_runtime.scheduler.command_warning_seconds', 600)
                ? ApplicationRuntimeHelper::STATUS_CRITICAL
                : ApplicationRuntimeHelper::STATUS_HEALTHY;

            $this->schedulerService->recordCommandEnd($command, $status, $duration);
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Scheduler finish monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleScheduledTaskFailed(ScheduledTaskFailed $event): void
    {
        try {
            $command = $this->scheduledCommandName($event);
            $this->schedulerService->recordCommandFailure($command, (string) $event->exception->getMessage());
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Scheduler failure monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        try {
            $jobId = (string) $event->job->getJobId();
            self::$jobStartTimes[$jobId] = microtime(true);
            $this->queueMonitorService->recordJobStart(
                $jobId,
                $this->jobName($event),
                getmypid() ?: null
            );
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Queue start monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        try {
            $jobId = (string) $event->job->getJobId();
            $duration = $this->duration(self::$jobStartTimes, $jobId);
            $this->queueMonitorService->recordJobEnd($jobId, $duration);
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Queue processed monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleJobFailed(JobFailed $event): void
    {
        try {
            $jobId = (string) $event->job->getJobId();
            $this->queueMonitorService->recordJobFailure($jobId);
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Queue failure monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleJobRetrying(JobRetrying $event): void
    {
        try {
            $jobId = (string) $event->job->getJobId();
            $this->queueMonitorService->recordJobFailure($jobId);
        } catch (Throwable $exception) {
            Log::channel('payin')->warning('Queue retry monitor failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function scheduledCommandName(ScheduledTaskStarting|ScheduledTaskFinished|ScheduledTaskFailed $event): string
    {
        $summary = $event->task->getSummaryForDisplay();

        return $summary !== '' ? $summary : 'scheduled-task';
    }

    private function jobName(JobProcessing $event): string
    {
        $payload = $event->job->payload();
        $displayName = $payload['displayName'] ?? null;

        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        return class_basename($payload['job'] ?? 'job');
    }

  /**
   * @param  array<string, float>  $store
   */
    private function duration(array &$store, string $key): float
    {
        $start = $store[$key] ?? microtime(true);
        unset($store[$key]);

        return max(0, microtime(true) - $start);
    }
}
