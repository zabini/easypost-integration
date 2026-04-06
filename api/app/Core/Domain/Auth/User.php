<?php

namespace App\Core\Domain\Auth;

use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Auth\ValueObjects\UserName;

final readonly class User
{
    public function __construct(
        private int $id,
        private UserName $name,
        private Email $email,
        private string $passwordHash,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name->value();
    }

    public function email(): string
    {
        return $this->email->value();
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id(),
            'name' => $this->name(),
            'email' => $this->email(),
        ];
    }
}
