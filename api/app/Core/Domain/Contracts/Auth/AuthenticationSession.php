<?php

namespace App\Core\Domain\Contracts\Auth;

interface AuthenticationSession
{
    public function login(int $userId): void;

    public function logout(): void;

    public function currentUserId(): ?int;
}
