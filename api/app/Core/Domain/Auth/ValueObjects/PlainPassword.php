<?php

namespace App\Core\Domain\Auth\ValueObjects;

use InvalidArgumentException;

final readonly class PlainPassword
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (mb_strlen($value) < 8) {
            throw new InvalidArgumentException('The password field must be at least 8 characters.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
