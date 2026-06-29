<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OpsDashboardPlaceholderService;
use App\Services\Dashboard\PaymentDashboardService;
use App\Services\Dashboard\SystemService;
use App\Services\Dashboard\TrafficDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpsDashboardController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService,
        private readonly OpsDashboardPlaceholderService $placeholderService,
        private readonly PaymentDashboardService $paymentDashboardService,
        private readonly TrafficDashboardService $trafficDashboardService,
    ) {
    }

    /**
     * Display the OPS dashboard.
     */
    public function index(): View
    {
        $serverInfo = $this->systemService->serverInfo();
        $overviewCards = $this->systemService->overviewCards($serverInfo);
        $gatewayPayments = $this->paymentDashboardService->gatewaySections();
        $traffic = $this->trafficDashboardService->dashboardPayload(5);

        return view('admin.dashboard.index', [
            'serverInfo' => $serverInfo,
            'overviewCards' => $overviewCards,
            'traffic' => $traffic,
            'gatewayPayments' => $gatewayPayments,
            'transactions' => $this->paymentDashboardService->recentTransactions(),
            'alerts' => $this->placeholderService->alerts(),
            'refreshIntervals' => $this->placeholderService->refreshIntervals(),
            'chartData' => [
                'overview' => collect($overviewCards)->mapWithKeys(fn (array $card) => [
                    $card['key'] => $card['sparkline'],
                ])->all(),
                'payments' => collect($gatewayPayments)->mapWithKeys(fn (array $section) => [
                    $section['key'] => collect($section['cards'])->mapWithKeys(fn (array $card) => [
                        $card['key'] => $card['sparkline'],
                    ])->all(),
                ])->all(),
            ],
        ]);
    }

    /**
     * Live payment metrics for dashboard polling.
     */
    public function paymentMetrics(): JsonResponse
    {
        return response()->json($this->paymentDashboardService->paymentMetricsPayload());
    }

    /**
     * Live API traffic metrics for dashboard polling.
     */
    public function trafficMetrics(Request $request): JsonResponse
    {
        $minutes = (int) $request->query('minutes', 5);

        return response()->json($this->trafficDashboardService->dashboardPayload($minutes));
    }
}
