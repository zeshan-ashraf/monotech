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
            'easypaisa_cron.status_workers.pending_for_8' => 300,
            'easypaisa_cron.status_workers.pending_for_10' => 400,
            'easypaisa_cron.status_workers.max_workers' => 10,
        ]);

        $this->allocator = new EasypaisaStatusWorkerAllocator();
    }

    /**
     * @dataProvider pendingWorkerMapping
     */
    public function test_pending_to_worker_mapping(int $pending, int $desired): void
    {
        $this->assertSame($desired, $this->allocator->workerCount($pending));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function pendingWorkerMapping(): array
    {
        return [
            '0 pending' => [0, 0],
            '1 pending' => [1, 2],
            '99 pending' => [99, 2],
            '100 pending' => [100, 4],
            '150 pending' => [150, 4],
            '199 pending' => [199, 4],
            '200 pending' => [200, 6],
            '250 pending' => [250, 6],
            '299 pending' => [299, 6],
            '300 pending' => [300, 8],
            '350 pending' => [350, 8],
            '399 pending' => [399, 8],
            '400 pending' => [400, 10],
            '450 pending' => [450, 10],
            '499 pending' => [499, 10],
            '500 pending' => [500, 10],
            '523 pending' => [523, 10],
        ];
    }

    public function test_scale_up_spawns_only_the_gap(): void
    {
        $this->assertSame(2, $this->allocator->workersToSpawn(2, 0));
        $this->assertSame(2, $this->allocator->workersToSpawn(4, 2));
        $this->assertSame(2, $this->allocator->workersToSpawn(6, 4));
        $this->assertSame(2, $this->allocator->workersToSpawn(8, 6));
        $this->assertSame(2, $this->allocator->workersToSpawn(10, 8));
        $this->assertSame(0, $this->allocator->workersToSpawn(10, 10));
        $this->assertSame(0, $this->allocator->workersToSpawn(4, 6));
        $this->assertSame(0, $this->allocator->workersToSpawn(0, 2));
    }

    public function test_worker_count_never_exceeds_ten(): void
    {
        config(['easypaisa_cron.status_workers.max_workers' => 100]);
        $allocator = new EasypaisaStatusWorkerAllocator();

        $this->assertSame(10, $allocator->maxWorkers());
        $this->assertSame(10, $allocator->workerCount(500));
        $this->assertSame(0, $allocator->workersToSpawn(10, 12));
    }

    public function test_abandoned_claims_recover_only_when_no_live_workers(): void
    {
        $this->assertTrue($this->allocator->shouldRecoverAbandonedClaims(0));
        $this->assertFalse($this->allocator->shouldRecoverAbandonedClaims(1));
        $this->assertFalse($this->allocator->shouldRecoverAbandonedClaims(10));
    }
}
