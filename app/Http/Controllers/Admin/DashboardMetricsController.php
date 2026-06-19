<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;

class DashboardMetricsController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $metricsService
    ) {
    }

    public function index(): JsonResponse
    {
        $viewer = auth()->user();
        $clients = $this->metricsService->getVisibleClients($viewer);

        return response()->json([
            'poll_interval_seconds' => (int) config('dashboard_metrics.poll_interval_seconds', 20),
            'clients' => $this->metricsService->getMetricsPayloadForClients($clients),
        ]);
    }

    public function show(int $userId): JsonResponse
    {
        $viewer = auth()->user();

        if (!$this->metricsService->canViewClientMetrics($viewer, $userId)) {
            abort(403);
        }

        $client = $this->metricsService->getVisibleClients($viewer)->firstWhere('id', $userId);

        return response()->json(array_merge(
            [
                'user_id' => $userId,
                'name' => $client?->name,
            ],
            $this->metricsService->getMetrics($userId)
        ));
    }
}
