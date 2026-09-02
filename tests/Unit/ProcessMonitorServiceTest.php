<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApplicationRuntimeHelper;
use App\Services\Dashboard\ProcessMonitorService;
use App\Services\Dashboard\QueueMonitorService;
use App\Services\Dashboard\SchedulerService;
use Illuminate\Redis\Connections\Connection;
use Mockery;
use Tests\TestCase;

class ProcessMonitorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_clear_entry_delegates_queue_job_clear(): void
    {
        $queue = Mockery::mock(QueueMonitorService::class);
        $queue->shouldReceive('clearJob')->once()->with('job-1')->andReturn(true);

        $scheduler = Mockery::mock(SchedulerService::class);
        $redis = Mockery::mock(Connection::class);
        $redis->shouldReceive('del')->once()->with(ApplicationRuntimeHelper::KEY_PROCESS_CACHE)->andReturn(1);

        $service = new ProcessMonitorService($scheduler, $queue, $redis);

        $this->assertTrue($service->clearEntry(ApplicationRuntimeHelper::TYPE_QUEUE_JOB, 'job-1'));
    }

    public function test_clear_all_stuck_clears_queue_and_scheduler_trackers(): void
    {
        $queue = Mockery::mock(QueueMonitorService::class);
        $queue->shouldReceive('clearStuckJobs')->once()->andReturn(5);

        $scheduler = Mockery::mock(SchedulerService::class);
        $scheduler->shouldReceive('clearStuckCommands')->once()->andReturn(1);

        $redis = Mockery::mock(Connection::class);
        $redis->shouldReceive('hgetall')
            ->with(ApplicationRuntimeHelper::KEY_GATEWAY_PROCESSING)
            ->andReturn([]);
        $redis->shouldReceive('del')->once()->with(ApplicationRuntimeHelper::KEY_PROCESS_CACHE)->andReturn(1);

        $service = new ProcessMonitorService($scheduler, $queue, $redis);

        $this->assertSame(6, $service->clearAllStuck());
    }
}
