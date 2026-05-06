<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Auth\User;

final readonly class AuthenticatedSession
{
    public function __construct(
        public User $user,
        public string $accessToken,
    ) {
    }
}
