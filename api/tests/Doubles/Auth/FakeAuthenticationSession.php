<?php

namespace Tests\Doubles\Auth;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;

final class FakeAuthenticationSession implements AuthenticationSession
{
    public function __construct(private ?int $currentUserId = null) {}

    public function login(int $userId): string
    {
        $this->currentUserId = $userId;

        return 'fake-access-token';
    }

    public function logout(): void
    {
        $this->currentUserId = null;
    }

    public function currentUserId(): ?int
    {
        return $this->currentUserId;
    }
}
