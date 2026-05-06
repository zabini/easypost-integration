<?php

namespace App\Core\Domain\Exceptions;

final class ShippingLabelAddressNotSupportedException extends BusinessException
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

    public function responseStatusCode(): int
    {
        return 422;
    }

    /**
     * @return array<string, mixed>
     */
    public function responseBody(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors(),
        ];
    }
}
