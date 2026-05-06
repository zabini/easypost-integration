<?php

namespace App\Core\Domain\Exceptions;

final class ShippingLabelUspsRateUnavailableException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('No USPS rate is available for the provided shipment.');
    }
}
