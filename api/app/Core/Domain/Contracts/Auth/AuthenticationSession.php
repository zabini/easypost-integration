<?php

namespace App\Core\Domain\Contracts\Auth;

interface AuthenticationSession
{
    public function login(int $userId): string;

    public function logout(): void;

    public function currentUserId(): ?int;
}
