<?php

namespace App\Core\Application\Auth;

final readonly class Login
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
