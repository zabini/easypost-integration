<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_signs_up_a_user_successfully(): void
    {
        $response = $this->postJson('/auth/signup', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('12345678', $user->password));
        $this->assertAuthenticatedAs($user);

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

    public function test_it_rejects_a_duplicate_email_during_signup(): void
    {
        User::factory()->create([
            'name' => 'Existing User',
            'email' => 'jane@example.com',
        ]);

        $response = $this->postJson('/auth/signup', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['This email is already registered.'],
                ],
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }
}
