<?php

namespace Tests\Unit;

use App\Services\EasypaisaStatusWorkerAllocator;
use Tests\TestCase;

class EasypaisaStatusWorkerAllocatorTest extends TestCase
{
    private EasypaisaStatusWorkerAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'easypaisa_cron.status_workers.pending_for_2' => 100,
            'easypaisa_cron.status_workers.pending_for_4' => 200,
            'easypaisa_cron.status_workers.pending_for_6' => 300,
            'easypaisa_cron.status_workers.pending_for_10' => 400,
            'easypaisa_cron.status_workers.stop_at_pending' => 500,
            'easypaisa_cron.status_workers.max_workers' => 10,
        ]);

        $this->allocator = new EasypaisaStatusWorkerAllocator();
    }

    public function test_100_eligible_pending_uses_zero_workers(): void
    {
        $this->assertSame(0, $this->allocator->workerCount(100));
    }

    public function test_101_eligible_pending_uses_2_workers(): void
    {
        $this->assertSame(2, $this->allocator->workerCount(101));
    }

    public function test_200_eligible_pending_uses_2_workers(): void
    {
        $this->assertSame(2, $this->allocator->workerCount(200));
    }

    public function test_201_eligible_pending_uses_4_workers(): void
    {
        $this->assertSame(4, $this->allocator->workerCount(201));
    }

    public function test_300_eligible_pending_uses_4_workers(): void
    {
        $this->assertSame(4, $this->allocator->workerCount(300));
    }

    public function test_301_eligible_pending_uses_6_workers(): void
    {
        $this->assertSame(6, $this->allocator->workerCount(301));
    }

    public function test_400_eligible_pending_uses_6_workers(): void
    {
        $this->assertSame(6, $this->allocator->workerCount(400));
    }

    public function test_401_eligible_pending_uses_10_workers(): void
    {
        $this->assertSame(10, $this->allocator->workerCount(401));
    }

    public function test_499_eligible_pending_uses_10_workers(): void
    {
        $this->assertSame(10, $this->allocator->workerCount(499));
    }

    public function test_500_eligible_pending_uses_zero_workers_and_stops_api(): void
    {
        $this->assertSame(0, $this->allocator->workerCount(500));
        $this->assertTrue($this->allocator->shouldStopNewApiRequests(500));
        $this->assertTrue($this->allocator->shouldStopNewApiRequests(523));
    }

    public function test_zero_eligible_pending_uses_zero_workers(): void
    {
        $this->assertSame(0, $this->allocator->workerCount(0));
        $this->assertFalse($this->allocator->shouldStopNewApiRequests(0));
    }

    public function test_worker_count_never_exceeds_ten(): void
    {
        config(['easypaisa_cron.status_workers.max_workers' => 100]);
        $allocator = new EasypaisaStatusWorkerAllocator();

        $this->assertSame(10, $allocator->maxWorkers());
        $this->assertSame(10, $allocator->workerCount(499));
    }

    public function test_abandoned_claims_recover_only_when_no_live_workers(): void
    {
        $this->assertTrue($this->allocator->shouldRecoverAbandonedClaims(0));
        $this->assertFalse($this->allocator->shouldRecoverAbandonedClaims(1));
        $this->assertFalse($this->allocator->shouldRecoverAbandonedClaims(10));
    }
}
