<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAuthenticatedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_authentication_to_access_the_private_route(): void
    {
        $this->getJson('/auth/me')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_it_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->actingAs($user);

        $this->getJson('/auth/me')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ],
            ]);
    }
}
