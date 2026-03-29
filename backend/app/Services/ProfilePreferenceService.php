<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserPreferenceRepository;
use InvalidArgumentException;

class ProfilePreferenceService
{
    public function __construct(private readonly UserPreferenceRepository $userPreferenceRepository)
    {
    }

    public function updateThemePreference(User $user, string $theme): User
    {
        if (!in_array($theme, [User::THEME_LIGHT, User::THEME_DARK], true)) {
            throw new InvalidArgumentException('Tema inválido para persistência.');
        }

        return $this->userPreferenceRepository->updateThemePreference($user, $theme);
    }
}
