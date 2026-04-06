<?php

namespace App\Core\Domain\ShippingLabel;

use DateTimeImmutable;

final readonly class ShippingLabel
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private string $easypostShipmentId,
        private ?string $easypostRateId,
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
        private ?array $rawResponse,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function easypostShipmentId(): string
    {
        return $this->easypostShipmentId;
    }

    public function easypostRateId(): ?string
    {
        return $this->easypostRateId;
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

    public function rawResponse(): ?array
    {
        return $this->rawResponse;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
