<?php

namespace Tests\Doubles\ShippingLabel;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelGateway;
use App\Core\Domain\ShippingLabel\PurchasedShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;
use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelRate;
use App\Core\Domain\ShippingLabel\ShippingLabelShipment;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;

final class FakeShippingLabelGateway implements ShippingLabelGateway
{
    public ?ShippingLabelShipment $createdShipment = null;

    public ?ShippingLabelPurchase $purchase = null;

    /**
     * @param  list<ShippingLabelRate>  $rates
     */
    public function __construct(private array $rates = []) {}

    public function createShipment(ShippingLabelShipment $shipment): ShippingLabelQuote
    {
        $this->createdShipment = $shipment;

        return new ShippingLabelQuote(
            shipmentId: 'shp_test_123',
            fromAddress: $shipment->fromAddress(),
            toAddress: $shipment->toAddress(),
            parcel: $shipment->parcel(),
            rates: $this->rates !== [] ? $this->rates : [
                new ShippingLabelRate(
                    id: 'rate_ups_ground',
                    carrier: 'UPS',
                    service: 'Ground',
                    rateAmount: '11.20',
                    rateCurrency: 'USD',
                ),
                new ShippingLabelRate(
                    id: 'rate_usps_priority',
                    carrier: 'USPS',
                    service: 'Priority',
                    rateAmount: '8.68',
                    rateCurrency: 'USD',
                ),
                new ShippingLabelRate(
                    id: 'rate_usps_express',
                    carrier: 'USPS',
                    service: 'Priority Express',
                    rateAmount: '7.50',
                    rateCurrency: 'USD',
                ),
            ],
            rawResponse: [
                'id' => 'shp_test_123',
            ],
        );
    }

    public function buyShipment(ShippingLabelPurchase $purchase): PurchasedShippingLabel
    {
        $this->purchase = $purchase;

        return new PurchasedShippingLabel(
            shipmentId: $purchase->shipmentId(),
            rateId: $purchase->rateId(),
            trackingCode: '9400100000000000000000',
            labelUrl: 'https://example.test/label.pdf',
            carrier: 'USPS',
            service: 'Priority Express',
            rateAmount: '7.50',
            rateCurrency: 'USD',
            status: ShippingLabelStatus::Purchased,
            fromAddress: $this->createdShipment?->fromAddress() ?? [],
            toAddress: $this->createdShipment?->toAddress() ?? [],
            parcel: $this->createdShipment?->parcel() ?? [],
            rawResponse: [
                'id' => 'shp_test_123',
                'postage_label' => [
                    'label_pdf_url' => 'https://example.test/label.pdf',
                ],
            ],
        );
    }
}
