<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApplicationRuntimeHelper;
use App\Helpers\ProcessHelper;
use App\Services\Dashboard\QueueMonitorService;
use Illuminate\Redis\Connections\Connection;
use Mockery;
use Tests\TestCase;

class QueueMonitorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_prune_stale_jobs_removes_old_tracker_entries(): void
    {
        $oldPayload = json_encode([
            'name' => 'App\\Jobs\\SendPayinCallbackJob',
            'started_at' => now()->timestamp - 7200,
            'pid' => 123,
        ]);
        $freshPayload = json_encode([
            'name' => 'App\\Jobs\\SendPayinCallbackJob',
            'started_at' => now()->timestamp - 10,
            'pid' => 456,
        ]);

        $redis = Mockery::mock(Connection::class);
        $redis->shouldReceive('hgetall')
            ->once()
            ->with(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING)
            ->andReturn([
                'job-old' => $oldPayload,
                'job-fresh' => $freshPayload,
            ]);
        $redis->shouldReceive('hdel')
            ->once()
            ->with(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, 'job-old')
            ->andReturn(1);
        $redis->shouldReceive('del')->andReturn(1);

        $service = new QueueMonitorService($redis);

        $this->assertSame(1, $service->pruneStaleJobs(3600));
    }

    public function test_clear_stuck_jobs_removes_entries_past_threshold(): void
    {
        $payload = json_encode([
            'name' => 'App\\Jobs\\SendPayinCallbackJob',
            'started_at' => now()->timestamp - 400,
            'pid' => 99,
        ]);

        $redis = Mockery::mock(Connection::class);
        $redis->shouldReceive('hgetall')
            ->once()
            ->with(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING)
            ->andReturn(['job-1' => $payload]);
        $redis->shouldReceive('hdel')
            ->once()
            ->with(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, 'job-1')
            ->andReturn(1);
        $redis->shouldReceive('del')->andReturn(1);

        $service = new QueueMonitorService($redis);

        $this->assertSame(1, $service->clearStuckJobs(300));
    }

    public function test_clear_job_removes_a_single_tracker_entry(): void
    {
        $redis = Mockery::mock(Connection::class);
        $redis->shouldReceive('hdel')
            ->once()
            ->with(ApplicationRuntimeHelper::KEY_QUEUE_PROCESSING, 'job-1')
            ->andReturn(1);
        $redis->shouldReceive('del')->andReturn(1);

        $service = new QueueMonitorService($redis);

        $this->assertTrue($service->clearJob('job-1'));
        $this->assertFalse($service->clearJob(''));
    }

    public function test_stale_runtime_entry_is_detected_by_age(): void
    {
        $this->assertTrue(ProcessHelper::isStaleMonitoredEntry([
            'started_at' => now()->timestamp - 4000,
            'pid' => 1,
        ], 3600));

        $this->assertFalse(ProcessHelper::isStaleMonitoredEntry([
            'started_at' => now()->timestamp - 10,
            'pid' => 0,
        ], 3600));
    }

    public function test_non_positive_pid_is_not_alive(): void
    {
        $this->assertFalse(ProcessHelper::isProcessAlive(0));
        $this->assertFalse(ProcessHelper::isProcessAlive(-1));
    }
}
