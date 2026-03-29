<?php

use App\Http\Controllers\ObservabilityDashboardController;
use App\Http\Middleware\EnsureObservabilityAllowlisted;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureObservabilityAllowlisted::class])
    ->prefix('infra/observability')
    ->group(function (): void {
        Route::get('/', [ObservabilityDashboardController::class, 'index']);
    });
