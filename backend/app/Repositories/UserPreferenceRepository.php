<?php

namespace App\Repositories;

use App\Models\User;

class UserPreferenceRepository
{
    public function updateThemePreference(User $user, string $theme): User
    {
        $user->forceFill([
            'theme_preference' => $theme,
        ])->save();

        return $user->refresh();
    }
}
