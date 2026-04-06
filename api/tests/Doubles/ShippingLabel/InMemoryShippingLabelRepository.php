<?php

namespace Tests\Doubles\ShippingLabel;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\ShippingLabel\ShippingLabel;

final class InMemoryShippingLabelRepository implements ShippingLabelRepository
{
    /** @var array<int, ShippingLabel> */
    private array $items = [];

    private int $nextId = 1;

    public function create(ShippingLabel $shippingLabel): ShippingLabel
    {
        $created = new ShippingLabel(
            id: $this->nextId,
            userId: $shippingLabel->userId(),
            easypostShipmentId: $shippingLabel->easypostShipmentId(),
            easypostRateId: $shippingLabel->easypostRateId(),
            trackingCode: $shippingLabel->trackingCode(),
            labelUrl: $shippingLabel->labelUrl(),
            carrier: $shippingLabel->carrier(),
            service: $shippingLabel->service(),
            rateAmount: $shippingLabel->rateAmount(),
            rateCurrency: $shippingLabel->rateCurrency(),
            status: $shippingLabel->status(),
            fromAddress: $shippingLabel->fromAddress(),
            toAddress: $shippingLabel->toAddress(),
            parcel: $shippingLabel->parcel(),
            rawResponse: $shippingLabel->rawResponse(),
        );

        $this->items[$this->nextId] = $created;
        $this->nextId++;

        return $created;
    }

    public function findByIdAndUserId(int $id, int $userId): ?ShippingLabel
    {
        $shippingLabel = $this->items[$id] ?? null;

        if ($shippingLabel === null || $shippingLabel->userId() !== $userId) {
            return null;
        }

        return $shippingLabel;
    }

    public function findByUserId(int $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (ShippingLabel $shippingLabel): bool => $shippingLabel->userId() === $userId,
        ));
    }
}
