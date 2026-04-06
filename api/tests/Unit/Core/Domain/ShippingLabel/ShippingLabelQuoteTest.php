<?php

namespace Tests\Unit\Core\Domain\ShippingLabel;

use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelRate;
use PHPUnit\Framework\TestCase;

class ShippingLabelQuoteTest extends TestCase
{
    public function test_it_returns_the_lowest_rate_for_the_given_carrier(): void
    {
        $quote = new ShippingLabelQuote(
            shipmentId: 'shp_test_123',
            fromAddress: [],
            toAddress: [],
            parcel: [],
            rates: [
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
            rawResponse: [],
        );

        $selectedRate = $quote->findLowestRateByCarrier('usps');

        $this->assertNotNull($selectedRate);
        $this->assertSame('rate_usps_express', $selectedRate->id());
    }

    public function test_it_returns_null_when_the_carrier_is_not_available(): void
    {
        $quote = new ShippingLabelQuote(
            shipmentId: 'shp_test_123',
            fromAddress: [],
            toAddress: [],
            parcel: [],
            rates: [
                new ShippingLabelRate(
                    id: 'rate_ups_ground',
                    carrier: 'UPS',
                    service: 'Ground',
                    rateAmount: '11.20',
                    rateCurrency: 'USD',
                ),
            ],
            rawResponse: [],
        );

        $this->assertNull($quote->findLowestRateByCarrier('USPS'));
    }
}
