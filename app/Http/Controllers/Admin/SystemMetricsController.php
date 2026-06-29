<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ApplicationRuntimeService;
use Illuminate\Http\JsonResponse;

class SystemMetricsController extends Controller
{
    public function __construct(
        private readonly ApplicationRuntimeService $runtimeService
    ) {
    }

    /**
     * Structured application runtime metrics for the OPS dashboard.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json($this->runtimeService->collect());
    }
}
