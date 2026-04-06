<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingLabelNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Shipping label not found.');
    }
}
