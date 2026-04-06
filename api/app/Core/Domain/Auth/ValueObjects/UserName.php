<?php

namespace App\Core\Domain\Auth\ValueObjects;

use InvalidArgumentException;

final readonly class UserName
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException('The name field is required.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
