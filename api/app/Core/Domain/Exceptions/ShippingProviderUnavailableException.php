<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingProviderUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'Shipping provider is unavailable.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
