<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class ShippingLabelUspsRateUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No USPS rate is available for the provided shipment.');
    }
}
