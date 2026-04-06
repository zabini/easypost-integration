<?php

namespace App\Core\Domain\ShippingLabel;

final readonly class ShippingLabelPurchase
{
    public function __construct(
        private string $shipmentId,
        private string $rateId,
    ) {
    }

    public function shipmentId(): string
    {
        return $this->shipmentId;
    }

    public function rateId(): string
    {
        return $this->rateId;
    }
}
