<?php

namespace App\Core\Domain\ShippingLabel;

final readonly class ShippingLabelShipment
{
    public function __construct(
        private array $fromAddress,
        private array $toAddress,
        private array $parcel,
    ) {
    }

    public function fromAddress(): array
    {
        return $this->fromAddress;
    }

    public function toAddress(): array
    {
        return $this->toAddress;
    }

    public function parcel(): array
    {
        return $this->parcel;
    }
}
