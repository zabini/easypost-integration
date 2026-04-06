<?php

namespace App\Infra\Http\Controller\ShippingLabel;

use App\Core\Domain\ShippingLabel\ShippingLabel;

final class ShippingLabelResponseData
{
    public static function fromDomain(ShippingLabel $shippingLabel): array
    {
        return [
            'id' => $shippingLabel->id(),
            'tracking_code' => $shippingLabel->trackingCode(),
            'label_url' => $shippingLabel->labelUrl(),
            'carrier' => $shippingLabel->carrier(),
            'service' => $shippingLabel->service(),
            'rate_amount' => $shippingLabel->rateAmount(),
            'rate_currency' => $shippingLabel->rateCurrency(),
            'status' => $shippingLabel->status()->value,
            'from_address' => $shippingLabel->fromAddress(),
            'to_address' => $shippingLabel->toAddress(),
            'parcel' => $shippingLabel->parcel(),
            'created_at' => $shippingLabel->createdAt()?->format(DATE_ATOM),
            'updated_at' => $shippingLabel->updatedAt()?->format(DATE_ATOM),
        ];
    }
}
