<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_persist_dark_theme_preference(): void
    {
        $user = User::factory()->create([
            'theme_preference' => User::THEME_LIGHT,
        ]);

        $this->actingAs($user)
            ->patchJson('/api/profile/theme', ['theme' => User::THEME_DARK])
            ->assertOk()
            ->assertJsonPath('data.theme', User::THEME_DARK);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme_preference' => User::THEME_DARK,
        ]);
    }

    public function test_authenticated_user_can_load_theme_preference_from_database(): void
    {
        $user = User::factory()->create([
            'theme_preference' => User::THEME_DARK,
        ]);

        $this->actingAs($user)
            ->getJson('/api/profile/theme')
            ->assertOk()
            ->assertJsonPath('data.theme', User::THEME_DARK);
    }
}
