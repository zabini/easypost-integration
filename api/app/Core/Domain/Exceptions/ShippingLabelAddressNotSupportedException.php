<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingLabelAddressNotSupportedException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        private array $errors,
        string $message = 'The given data was invalid.',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
