<?php

namespace App\Services;

class EasypaisaStatusWorkerAllocator
{
    public function workerCount(int $eligiblePending): int
    {
        $stopAt = $this->stopAtPending();
        $forSix = $this->pendingForSix();
        $forFour = $this->pendingForFour();
        $max = $this->maxWorkers();

        if ($eligiblePending <= 0 || $eligiblePending >= $stopAt) {
            return 0;
        }

        if ($eligiblePending >= $forSix) {
            return $max;
        }

        if ($eligiblePending >= $forFour) {
            return min(4, $max);
        }

        return min(2, $max);
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

    public function pendingForFour(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_4', 300);
    }

    public function pendingForSix(): int
    {
        return (int) config('easypaisa_cron.status_workers.pending_for_6', 400);
    }

    public function maxWorkers(): int
    {
        return min(6, (int) config('easypaisa_cron.status_workers.max_workers', 6));
    }
}
