<?php

namespace App\Core\Domain\ShippingLabel;

final readonly class PurchasedShippingLabel
{
    public function __construct(
        private string $shipmentId,
        private string $rateId,
        private ?string $trackingCode,
        private string $labelUrl,
        private string $carrier,
        private string $service,
        private string $rateAmount,
        private string $rateCurrency,
        private ShippingLabelStatus $status,
        private array $fromAddress,
        private array $toAddress,
        private array $parcel,
        private array $rawResponse,
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

    public function trackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function labelUrl(): string
    {
        return $this->labelUrl;
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

    public function status(): ShippingLabelStatus
    {
        return $this->status;
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

    public function rawResponse(): array
    {
        return $this->rawResponse;
    }
}
