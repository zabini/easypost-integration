<?php

namespace App\Core\Domain\Exceptions;

final class ShippingLabelNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Shipping label not found.');
    }

    public function responseStatusCode(): int
    {
        return 404;
    }
}
