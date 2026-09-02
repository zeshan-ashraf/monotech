<?php

namespace App\Services;

class EasypaisaStatusWorkerAllocator
{
    public function workerCount(int $eligiblePending): int
    {
        $stopAt = $this->stopAtPending();
        $max = $this->maxWorkers();

        if ($eligiblePending <= 0 || $eligiblePending >= $stopAt) {
            return 0;
        }

        if ($eligiblePending > $this->pendingForTen()) {
            return min(10, $max);
        }

        if ($eligiblePending > $this->pendingForSix()) {
            return min(6, $max);
        }

        if ($eligiblePending > $this->pendingForFour()) {
            return min(4, $max);
        }

        if ($eligiblePending > $this->pendingForTwo()) {
            return min(2, $max);
        }

        return 0;
    }

    public function shouldStopNewApiRequests(int $eligiblePending): bool
    {
        return $eligiblePending >= $this->stopAtPending();
    }

    public function shouldRecoverAbandonedClaims(int $liveWorkerCount): bool
    {
        return $liveWorkerCount === 0;
    }

    public function stopAtPending(): int
    {
        return (int) config('easypaisa_cron.status_workers.stop_at_pending', 500);
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

    public function pendingForTen(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_10', 400);
    }

    public function maxWorkers(): int
    {
        return min(10, (int) config('easypaisa_cron.status_workers.max_workers', 10));
    }
}
