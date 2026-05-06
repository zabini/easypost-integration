<?php

namespace App\Core\Domain\Exceptions;

final class ShippingProviderRequestException extends BusinessException
{
    public function __construct(string $message = 'Shipping provider rejected the request.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
