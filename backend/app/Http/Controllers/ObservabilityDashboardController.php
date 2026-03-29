<?php

namespace App\Http\Controllers;

use App\Services\ObservabilityDashboardService;
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
}
