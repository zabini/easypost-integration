<?php

namespace App\Core\Domain\Contracts\Auth;

use App\Core\Domain\Auth\ValueObjects\PlainPassword;

interface PasswordHasher
{
    public function hash(PlainPassword $password): string;

    public function check(string $plainText, string $hashedPassword): bool;
}
