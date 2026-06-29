<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApplicationRuntimeHelper;
use App\Services\Dashboard\ApplicationRuntimeService;
use App\Services\Dashboard\PHPFpmService;
use App\Services\Dashboard\ProcessMonitorService;
use App\Services\Dashboard\QueueMonitorService;
use App\Services\Dashboard\SchedulerService;
use Tests\TestCase;

class ApplicationRuntimeServiceTest extends TestCase
{
    public function test_collect_returns_expected_top_level_keys(): void
    {
        $phpFpm = $this->createMock(PHPFpmService::class);
        $scheduler = $this->createMock(SchedulerService::class);
        $queue = $this->createMock(QueueMonitorService::class);
        $process = $this->createMock(ProcessMonitorService::class);

        $phpFpm->method('collect')->willReturn([
            'status' => ApplicationRuntimeHelper::STATUS_HEALTHY,
            'status_label' => 'Healthy',
            'status_color' => 'success',
            'busy_workers' => 2,
            'total_workers' => 10,
            'worker_utilization' => 20,
            'listen_queue' => 0,
            'max_children_reached' => 0,
        ]);
        $scheduler->method('collect')->willReturn([
            'status' => ApplicationRuntimeHelper::STATUS_HEALTHY,
            'status_label' => 'Healthy',
            'status_color' => 'success',
            'last_tick' => '10:00 AM',
            'running_commands' => 0,
            'failed_today' => 0,
            'seconds_since_tick' => 30,
        ]);
        $queue->method('collect')->willReturn([
            'status' => ApplicationRuntimeHelper::STATUS_HEALTHY,
            'status_label' => 'Healthy',
            'status_color' => 'success',
            'pending_jobs' => 0,
            'processing_jobs' => 0,
            'failed_jobs' => 0,
            'worker_count' => 1,
            'longest_running_seconds' => 0,
        ]);
        $process->method('collect')->willReturn([
            'status' => ApplicationRuntimeHelper::STATUS_HEALTHY,
            'status_label' => 'Healthy',
            'status_color' => 'success',
            'total' => 0,
            'critical_count' => 0,
            'warning_count' => 0,
            'processes' => [],
        ]);

        $service = new ApplicationRuntimeService($phpFpm, $scheduler, $queue, $process);
        $payload = $service->collect();

        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('php_fpm', $payload);
        $this->assertArrayHasKey('scheduler', $payload);
        $this->assertArrayHasKey('queue', $payload);
        $this->assertArrayHasKey('stuck_processes', $payload);
        $this->assertArrayHasKey('recommendations', $payload);
        $this->assertCount(4, $payload['summary']);
    }

    public function test_utilization_status_thresholds(): void
    {
        $this->assertSame(
            ApplicationRuntimeHelper::STATUS_HEALTHY,
            ApplicationRuntimeHelper::utilizationStatus(50)
        );
        $this->assertSame(
            ApplicationRuntimeHelper::STATUS_WARNING,
            ApplicationRuntimeHelper::utilizationStatus(75)
        );
        $this->assertSame(
            ApplicationRuntimeHelper::STATUS_CRITICAL,
            ApplicationRuntimeHelper::utilizationStatus(95)
        );
    }
}
