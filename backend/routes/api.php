<?php

use App\Http\Controllers\ObservabilityDashboardController;
use App\Http\Controllers\ProfilePreferenceController;
use App\Http\Middleware\EnsureObservabilityAllowlisted;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/profile/theme', [ProfilePreferenceController::class, 'showTheme']);
    Route::patch('/profile/theme', [ProfilePreferenceController::class, 'updateTheme']);

    Route::middleware(EnsureObservabilityAllowlisted::class)
        ->prefix('/infra/observability')
        ->group(function (): void {
            Route::get('/summary', [ObservabilityDashboardController::class, 'summary']);
        });
});
