<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservabilityAllowlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_not_in_allowlist_receives_forbidden(): void
    {
        config()->set('observability.allowlist', ['allowlisted@cactus.com']);

        $user = User::factory()->create([
            'email' => 'blocked@cactus.com',
        ]);

        $this->actingAs($user)
            ->get('/infra/observability')
            ->assertForbidden();
    }

    public function test_user_in_allowlist_by_identifier_is_authorized(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked@cactus.com',
        ]);

        config()->set('observability.allowlist', [(string) $user->id]);

        $this->actingAs($user)
            ->get('/infra/observability')
            ->assertOk()
            ->assertSeeText('Painel de Observabilidade');
    }

    public function test_user_in_allowlist_by_email_is_authorized(): void
    {
        $user = User::factory()->create([
            'email' => 'allowlisted@cactus.com',
        ]);

        config()->set('observability.allowlist', ['allowlisted@cactus.com']);

        $this->actingAs($user)
            ->get('/infra/observability')
            ->assertOk()
            ->assertSeeText('Painel de Observabilidade');
    }
}
