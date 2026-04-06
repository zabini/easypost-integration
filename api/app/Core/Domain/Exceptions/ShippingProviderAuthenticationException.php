<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingProviderAuthenticationException extends RuntimeException
{
    public function __construct(string $message = 'Shipping provider authentication failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
