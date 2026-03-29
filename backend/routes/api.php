<?php

use App\Http\Controllers\ProfilePreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::patch('/profile/theme', [ProfilePreferenceController::class, 'updateTheme']);
});
