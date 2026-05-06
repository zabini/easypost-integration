<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_out_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $loginResponse = $this->postJson('/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $accessToken = $loginResponse->json('meta.access_token');

        $this->assertNotEmpty($accessToken);
        [$tokenId] = explode('|', $accessToken);

        $this->withToken($accessToken)->postJson('/auth/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertNull(PersonalAccessToken::query()->find($tokenId));
    }
}
