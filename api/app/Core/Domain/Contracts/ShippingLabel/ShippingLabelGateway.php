<?php

namespace App\Core\Domain\Contracts\ShippingLabel;

use App\Core\Domain\ShippingLabel\PurchasedShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;
use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelShipment;

interface ShippingLabelGateway
{
    public function createShipment(ShippingLabelShipment $shipment): ShippingLabelQuote;

    public function buyShipment(ShippingLabelPurchase $purchase): PurchasedShippingLabel;
}
