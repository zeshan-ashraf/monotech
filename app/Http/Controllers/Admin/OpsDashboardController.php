<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\SystemService;
use Illuminate\View\View;

class OpsDashboardController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService
    ) {
    }

    /**
     * Display the OPS dashboard server information section.
     */
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'serverInfo' => $this->systemService->serverInfo(),
        ]);
    }
}
