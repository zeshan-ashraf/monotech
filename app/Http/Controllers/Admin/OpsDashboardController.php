<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OpsDashboardPlaceholderService;
use App\Services\Dashboard\PaymentDashboardService;
use App\Services\Dashboard\SystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OpsDashboardController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService,
        private readonly OpsDashboardPlaceholderService $placeholderService,
        private readonly PaymentDashboardService $paymentDashboardService,
    ) {
    }

    /**
     * Display the OPS dashboard.
     */
    public function index(): View
    {
        $serverInfo = $this->systemService->serverInfo();
        $overviewCards = $this->systemService->overviewCards($serverInfo);
        $payments = $this->paymentDashboardService->paymentsOverview();

        return view('admin.dashboard.index', [
            'serverInfo' => $serverInfo,
            'overviewCards' => $overviewCards,
            'payments' => $payments,
            'transactions' => $this->paymentDashboardService->recentTransactions(),
            'paymentStats' => $this->paymentDashboardService->paymentResponseStats(),
            'alerts' => $this->placeholderService->alerts(),
            'refreshIntervals' => $this->placeholderService->refreshIntervals(),
            'chartData' => [
                'overview' => collect($overviewCards)->mapWithKeys(fn (array $card) => [
                    $card['key'] => $card['sparkline'],
                ])->all(),
                'payments' => collect($payments)->mapWithKeys(fn (array $item) => [
                    $item['key'] => $item['sparkline'],
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
}
