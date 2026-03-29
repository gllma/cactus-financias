<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThemePreferenceRequest;
use App\Services\ProfilePreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilePreferenceController extends Controller
{
    public function __construct(private readonly ProfilePreferenceService $profilePreferenceService)
    {
    }

    public function showTheme(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'theme' => $this->profilePreferenceService->getThemePreference($request->user()),
            ],
        ]);
    }

    public function updateTheme(UpdateThemePreferenceRequest $request): JsonResponse
    {
        $user = $this->profilePreferenceService->updateThemePreference(
            user: $request->user(),
            theme: $request->validated('theme'),
        );

        return response()->json([
            'message' => 'Preferência de tema atualizada com sucesso.',
            'data' => [
                'theme' => $user->theme_preference,
            ],
        ]);
    }
}
