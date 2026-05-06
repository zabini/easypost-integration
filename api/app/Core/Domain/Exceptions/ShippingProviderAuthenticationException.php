<?php

namespace App\Core\Domain\Exceptions;

final class ShippingProviderAuthenticationException extends BusinessException
{
    public function __construct(string $message = 'Shipping provider authentication failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function responseStatusCode(): int
    {
        return 503;
    }
}
