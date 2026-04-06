<?php

namespace App\Core\Domain\Auth\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));

        if ($normalized === '' || ! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email field must be a valid email address.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
