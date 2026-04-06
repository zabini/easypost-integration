<?php

namespace App\Core\Domain\ShippingLabel;

final readonly class ShippingLabelRate
{
    public function __construct(
        private string $id,
        private string $carrier,
        private string $service,
        private string $rateAmount,
        private string $rateCurrency,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function carrier(): string
    {
        return $this->carrier;
    }

    public function service(): string
    {
        return $this->service;
    }

    public function rateAmount(): string
    {
        return $this->rateAmount;
    }

    public function rateCurrency(): string
    {
        return $this->rateCurrency;
    }
}
