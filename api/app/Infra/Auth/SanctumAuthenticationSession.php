<?php

namespace App\Infra\Auth;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Models\User;
use Illuminate\Http\Request;

final readonly class SanctumAuthenticationSession implements AuthenticationSession
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function login(int $userId): string
    {
        $user = User::query()->findOrFail($userId);

        return $user->createToken('auth_token')->plainTextToken;
    }

    public function logout(): void
    {
        $user = $this->request->user('sanctum');
        $currentAccessToken = $user?->currentAccessToken();

        if ($currentAccessToken !== null && method_exists($currentAccessToken, 'delete')) {
            $currentAccessToken->delete();
            return;
        }

        if ($user !== null) {
            $user->tokens()->delete();
        }
    }

    public function currentUserId(): ?int
    {
        return $this->request->user('sanctum')?->getAuthIdentifier();
    }
}
