<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function getVisibleClients(User $viewer): Collection
    {
        $exclude = config('dashboard_metrics.exclude_user_ids', []);

        if ($viewer->user_role === 'Client') {
            if (!$viewer->active || in_array($viewer->id, $exclude, true)) {
                return collect();
            }

            return collect([$viewer]);
        }

        if (in_array($viewer->user_role, config('dashboard_metrics.viewer_roles_all_clients', []), true)) {
            return User::query()
                ->where('user_role', 'Client')
                ->where('active', 1)
                ->whereNotIn('id', $exclude)
                ->orderBy('name', 'asc')
                ->get();
        }

        return collect();
    }

    public function canViewClientMetrics(User $viewer, int $targetUserId): bool
    {
        return $this->getVisibleClients($viewer)->contains('id', $targetUserId);
    }

    public function getMetrics(int $userId): array
    {
        return [
            'jc_success_rate' => $this->getSuccessRate($userId, 'jazzcash'),
            'ep_success_rate' => $this->getSuccessRate($userId, 'easypaisa'),
            'jc_pending' => $this->getPendingCount($userId, 'jazzcash'),
            'ep_pending' => $this->getPendingCount($userId, 'easypaisa'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMetricsPayloadForClients(Collection $clients): array
    {
        return $clients->map(function (User $client) {
            return array_merge(
                [
                    'user_id' => $client->id,
                    'name' => $client->name,
                ],
                $this->getMetrics($client->id)
            );
        })->values()->all();
    }

    // public function getSuccessRate(int $userId, string $txnType): float
    // {
    //     $since = Carbon::now()->subMinutes(
    //         (int) config('dashboard_metrics.success_rate_window_minutes', 5)
    //     );

    //     $successCount = Transaction::query()
    //         ->where('user_id', $userId)
    //         ->where('created_at', '>=', $since)
    //         ->where('status', 'success')
    //         ->where('txn_type', $txnType)
    //         ->count();

    //     $failedCount = Transaction::query()
    //         ->where('user_id', $userId)
    //         ->where('created_at', '>=', $since)
    //         ->where('status', 'failed')
    //         ->where('txn_type', $txnType)
    //         ->count();

    //     $total = $successCount + $failedCount;

    //     return $total > 0
    //         ? round(($successCount / $total) * 100, 2)
    //         : 0.0;
    // }
    public function getSuccessRate(int $userId, string $txnType): float
    {
        $since = Carbon::now()->subMinutes(10);

        $counts = Transaction::query()
            ->where('user_id', $userId)
            ->where('txn_type', $txnType)
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['success', 'failed'])
            ->selectRaw("
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count
            ")
            ->first();

        $successCount = $counts->success_count ?? 0;
        $failedCount = $counts->failed_count ?? 0;

        $total = $successCount + $failedCount;

        return $total > 0
            ? round(($successCount / $total) * 100, 2)
            : 0.0;
    }

    public function getPendingCount(int $userId, string $txnType): int
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('txn_type', $txnType)
            ->count();
    }
}
