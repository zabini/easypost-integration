<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingProviderUnexpectedResponseException extends RuntimeException
{
    public function __construct(string $message = 'Shipping provider returned an unexpected response.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
