<?php

namespace App\Core\Application\Auth;

final readonly class SignUp
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
