<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OpsDashboardPlaceholderService;
use App\Services\Dashboard\SystemService;
use Illuminate\View\View;

class OpsDashboardController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService,
        private readonly OpsDashboardPlaceholderService $placeholderService,
    ) {
    }

    /**
     * Display the OPS dashboard.
     */
    public function index(): View
    {
        $serverInfo = $this->systemService->serverInfo();
        $overviewCards = $this->systemService->overviewCards($serverInfo);

        return view('admin.dashboard.index', [
            'serverInfo' => $serverInfo,
            'overviewCards' => $overviewCards,
            'payments' => $this->placeholderService->paymentsOverview(),
            'transactions' => $this->placeholderService->recentTransactions(),
            'paymentStats' => $this->placeholderService->paymentResponseStats(),
            'alerts' => $this->placeholderService->alerts(),
            'refreshIntervals' => $this->placeholderService->refreshIntervals(),
            'chartData' => $this->placeholderService->chartDataForMain($overviewCards),
        ]);
    }
}
