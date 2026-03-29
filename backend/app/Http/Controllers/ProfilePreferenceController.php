<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThemePreferenceRequest;
use App\Services\ProfilePreferenceService;
use Illuminate\Http\JsonResponse;

class ProfilePreferenceController extends Controller
{
    public function __construct(private readonly ProfilePreferenceService $profilePreferenceService)
    {
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
