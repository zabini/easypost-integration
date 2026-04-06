<?php

namespace App\Infra\Persistence\ShippingLabel;

use App\Core\Domain\ShippingLabel\ShippingLabel as DomainShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use App\Models\ShippingLabel as EloquentShippingLabel;
use DateTimeImmutable;

final class ShippingLabelMapper
{
    public function toDomain(EloquentShippingLabel $shippingLabel): DomainShippingLabel
    {
        $status = $shippingLabel->status instanceof ShippingLabelStatus
            ? $shippingLabel->status
            : ShippingLabelStatus::from($shippingLabel->status);

        return new DomainShippingLabel(
            id: (int) $shippingLabel->getKey(),
            userId: (int) $shippingLabel->user_id,
            easypostShipmentId: $shippingLabel->easypost_shipment_id,
            easypostRateId: $shippingLabel->easypost_rate_id,
            trackingCode: $shippingLabel->tracking_code,
            labelUrl: $shippingLabel->label_url,
            carrier: $shippingLabel->carrier,
            service: $shippingLabel->service,
            rateAmount: $shippingLabel->rate_amount,
            rateCurrency: $shippingLabel->rate_currency,
            status: $status,
            fromAddress: $shippingLabel->from_address_json,
            toAddress: $shippingLabel->to_address_json,
            parcel: $shippingLabel->parcel_json,
            rawResponse: $shippingLabel->raw_response_json,
            createdAt: $shippingLabel->created_at === null
                ? null
                : DateTimeImmutable::createFromInterface($shippingLabel->created_at),
            updatedAt: $shippingLabel->updated_at === null
                ? null
                : DateTimeImmutable::createFromInterface($shippingLabel->updated_at),
        );
    }

    public function toEloquentAttributes(DomainShippingLabel $shippingLabel): array
    {
        return [
            'user_id' => $shippingLabel->userId(),
            'easypost_shipment_id' => $shippingLabel->easypostShipmentId(),
            'easypost_rate_id' => $shippingLabel->easypostRateId(),
            'tracking_code' => $shippingLabel->trackingCode(),
            'label_url' => $shippingLabel->labelUrl(),
            'carrier' => $shippingLabel->carrier(),
            'service' => $shippingLabel->service(),
            'rate_amount' => $shippingLabel->rateAmount(),
            'rate_currency' => $shippingLabel->rateCurrency(),
            'status' => $shippingLabel->status()->value,
            'from_address_json' => $shippingLabel->fromAddress(),
            'to_address_json' => $shippingLabel->toAddress(),
            'parcel_json' => $shippingLabel->parcel(),
            'raw_response_json' => $shippingLabel->rawResponse(),
        ];
    }
}
