<?php

namespace App\Services;

class EasypaisaStatusWorkerAllocator
{
    public function workerCount(int $eligiblePending): int
    {
        $max = $this->maxWorkers();

        if ($eligiblePending <= 0) {
            return 0;
        }

        if ($eligiblePending >= $this->pendingForTen()) {
            return min(10, $max);
        }

        if ($eligiblePending >= $this->pendingForEight()) {
            return min(8, $max);
        }

        if ($eligiblePending >= $this->pendingForFour()) {
            return min(6, $max);
        }

        if ($eligiblePending >= $this->pendingForTwo()) {
            return min(4, $max);
        }

        return min(2, $max);
    }

    public function workersToSpawn(int $desiredWorkers, int $liveWorkers): int
    {
        $desired = min($this->maxWorkers(), max(0, $desiredWorkers));
        $live = max(0, $liveWorkers);

        if ($desired <= $live) {
            return 0;
        }

        return $desired - $live;
    }

    public function shouldRecoverAbandonedClaims(int $liveWorkerCount): bool
    {
        return $liveWorkerCount === 0;
    }

    public function pendingForTwo(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_2', 100);
    }

    public function pendingForFour(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_4', 200);
    }

    public function pendingForSix(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_6', 300);
    }

    public function pendingForEight(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_8', $this->pendingForSix());
    }

    public function pendingForTen(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_10', 400);
    }

    public function maxWorkers(): int
    {
        return min(10, (int) config('easypaisa_cron.status_workers.max_workers', 10));
    }
}
