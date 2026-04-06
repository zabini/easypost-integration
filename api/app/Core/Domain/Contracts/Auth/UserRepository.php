<?php

namespace App\Core\Domain\Contracts\Auth;

use App\Core\Domain\Auth\User;
use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Auth\ValueObjects\UserName;

interface UserRepository
{
    public function existsByEmail(Email $email): bool;

    public function create(UserName $name, Email $email, string $passwordHash): User;

    public function findByEmail(Email $email): ?User;

    public function findById(int $id): ?User;
}
