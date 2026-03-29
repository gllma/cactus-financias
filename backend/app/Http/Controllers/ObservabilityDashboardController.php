<?php

namespace App\Http\Controllers;

use App\Services\ObservabilityDashboardService;
use App\Http\Requests\ObservabilitySummaryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

class ObservabilityDashboardController extends Controller
{
    public function __construct(private readonly ObservabilityDashboardService $observabilityDashboardService)
    {
    }

    public function index(): View
    {
        return view('observability.dashboard', [
            'summary' => $this->observabilityDashboardService->summary(),
        ]);
    }

    public function summary(ObservabilitySummaryRequest $request): JsonResponse
    {
        $periodMinutes = $request->periodMinutes();

        return response()->json([
            'data' => $this->observabilityDashboardService->summary($periodMinutes),
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'period_minutes' => $periodMinutes,
            ],
        ]);
    }
}
