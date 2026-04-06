<?php

namespace App\Infra\Auth;

use App\Core\Domain\Auth\ValueObjects\PlainPassword;
use App\Core\Domain\Contracts\Auth\PasswordHasher;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(private Hasher $hasher) {}

    public function hash(PlainPassword $password): string
    {
        return $this->hasher->make($password->value());
    }

    public function check(string $plainText, string $hashedPassword): bool
    {
        return $this->hasher->check($plainText, $hashedPassword);
    }
}
