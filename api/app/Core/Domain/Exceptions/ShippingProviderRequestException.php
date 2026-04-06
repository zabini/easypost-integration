<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingProviderRequestException extends RuntimeException
{
    public function __construct(string $message = 'Shipping provider rejected the request.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
