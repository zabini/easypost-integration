<?php

namespace App\Core\Domain\Exceptions;

final class ShippingProviderUnavailableException extends BusinessException
{
    public function __construct(string $message = 'Shipping provider is unavailable.', ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function responseStatusCode(): int
    {
        return 503;
    }
}
