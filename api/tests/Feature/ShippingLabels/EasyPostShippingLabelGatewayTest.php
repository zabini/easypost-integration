<?php

namespace Tests\Feature\ShippingLabels;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelGateway;
use App\Core\Domain\Exceptions\ShippingProviderAuthenticationException;
use App\Core\Domain\Exceptions\ShippingProviderRequestException;
use App\Core\Domain\Exceptions\ShippingProviderUnavailableException;
use App\Core\Domain\Exceptions\ShippingProviderUnexpectedResponseException;
use App\Core\Domain\ShippingLabel\PurchasedShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;
use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelShipment;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use App\Infra\Integration\ShippingLabel\EasyPostShippingLabelGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EasyPostShippingLabelGatewayTest extends TestCase
{
    public function test_it_creates_a_shipment_using_the_configured_api_key_and_maps_the_response(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'test_api_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake(function (Request $request) {
            $this->assertSame('https://api.easypost.com/v2/shipments', $request->url());
            $this->assertTrue($request->hasHeader('Authorization', 'Basic '.base64_encode('test_api_key:')));
            $this->assertSame('Jane Sender', $request->data()['shipment']['from_address']['name']);
            $this->assertSame('John Receiver', $request->data()['shipment']['to_address']['name']);
            $this->assertSame(12, $request->data()['shipment']['parcel']['weight']);

            return Http::response($this->shipmentResponse(), 200);
        });

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $quote = $gateway->createShipment($this->shipment());

        $this->assertInstanceOf(EasyPostShippingLabelGateway::class, $gateway);
        $this->assertInstanceOf(ShippingLabelQuote::class, $quote);
        $this->assertSame('shp_test_123', $quote->shipmentId());
        $this->assertCount(2, $quote->rates());
        $this->assertSame('rate_usps_123', $quote->rates()[0]->id());
        $this->assertSame('USPS', $quote->rates()[0]->carrier());
        $this->assertSame('Priority', $quote->rates()[0]->service());
        $this->assertSame('8.68', $quote->rates()[0]->rateAmount());
        $this->assertSame('USD', $quote->rates()[0]->rateCurrency());
    }

    public function test_it_buys_a_shipment_and_maps_the_purchased_label(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'test_api_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake(function (Request $request) {
            $this->assertSame('https://api.easypost.com/v2/shipments/shp_test_123/buy', $request->url());
            $this->assertTrue($request->hasHeader('Authorization', 'Basic '.base64_encode('test_api_key:')));
            $this->assertSame('rate_usps_123', $request->data()['rate']['id']);

            return Http::response($this->purchasedShipmentResponse(), 200);
        });

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $label = $gateway->buyShipment(new ShippingLabelPurchase(
            shipmentId: 'shp_test_123',
            rateId: 'rate_usps_123',
        ));

        $this->assertInstanceOf(PurchasedShippingLabel::class, $label);
        $this->assertSame('shp_test_123', $label->shipmentId());
        $this->assertSame('rate_usps_123', $label->rateId());
        $this->assertSame('9400100000000000000000', $label->trackingCode());
        $this->assertSame('https://example.test/label.pdf', $label->labelUrl());
        $this->assertSame('USPS', $label->carrier());
        $this->assertSame('Priority', $label->service());
        $this->assertSame('8.68', $label->rateAmount());
        $this->assertSame('USD', $label->rateCurrency());
        $this->assertSame(ShippingLabelStatus::Purchased, $label->status());
    }

    public function test_it_translates_authentication_failures(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'invalid_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake([
            'https://api.easypost.com/v2/shipments' => Http::response([
                'error' => [
                    'message' => 'Unauthorized',
                ],
            ], 401),
        ]);

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $this->expectException(ShippingProviderAuthenticationException::class);

        $gateway->createShipment($this->shipment());
    }

    public function test_it_translates_request_rejections_into_controlled_errors(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'test_api_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake([
            'https://api.easypost.com/v2/shipments' => Http::response([
                'error' => [
                    'message' => 'The to_address is invalid.',
                ],
            ], 422),
        ]);

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $this->expectException(ShippingProviderRequestException::class);
        $this->expectExceptionMessage('The to_address is invalid.');

        $gateway->createShipment($this->shipment());
    }

    public function test_it_translates_connection_failures(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'test_api_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake([
            'https://api.easypost.com/v2/shipments' => Http::failedConnection(),
        ]);

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $this->expectException(ShippingProviderUnavailableException::class);

        $gateway->createShipment($this->shipment());
    }

    public function test_it_rejects_unexpected_provider_responses(): void
    {
        config()->set('services.easypost', [
            'api_key' => 'test_api_key',
            'base_url' => 'https://api.easypost.com/v2',
            'timeout' => 10,
        ]);

        Http::fake([
            'https://api.easypost.com/v2/shipments' => Http::response([
                'id' => 'shp_test_123',
                'from_address' => $this->shipment()->fromAddress(),
                'to_address' => $this->shipment()->toAddress(),
                'parcel' => $this->shipment()->parcel(),
                'rates' => [
                    [
                        'id' => 'rate_usps_123',
                        'carrier' => 'USPS',
                    ],
                ],
            ], 200),
        ]);

        $gateway = $this->app->make(ShippingLabelGateway::class);

        $this->expectException(ShippingProviderUnexpectedResponseException::class);

        $gateway->createShipment($this->shipment());
    }

    public function test_the_gateway_contract_can_be_replaced_with_a_fake_in_tests(): void
    {
        $this->app->bind(ShippingLabelGateway::class, FakeShippingLabelGateway::class);

        $gateway = $this->app->make(ShippingLabelGateway::class);
        $quote = $gateway->createShipment($this->shipment());

        $this->assertInstanceOf(FakeShippingLabelGateway::class, $gateway);
        $this->assertSame('fake_shipment', $quote->shipmentId());
    }

    private function shipment(): ShippingLabelShipment
    {
        return new ShippingLabelShipment(
            fromAddress: [
                'name' => 'Jane Sender',
                'street1' => '417 Montgomery Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94104',
                'country' => 'US',
            ],
            toAddress: [
                'name' => 'John Receiver',
                'street1' => '388 Townsend St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94107',
                'country' => 'US',
            ],
            parcel: [
                'weight' => 12,
                'length' => 10,
                'width' => 7,
                'height' => 4,
            ],
        );
    }

    private function shipmentResponse(): array
    {
        return [
            'id' => 'shp_test_123',
            'from_address' => $this->shipment()->fromAddress(),
            'to_address' => $this->shipment()->toAddress(),
            'parcel' => $this->shipment()->parcel(),
            'rates' => [
                [
                    'id' => 'rate_usps_123',
                    'carrier' => 'USPS',
                    'service' => 'Priority',
                    'rate' => '8.68',
                    'currency' => 'USD',
                ],
                [
                    'id' => 'rate_ups_123',
                    'carrier' => 'UPS',
                    'service' => 'Ground',
                    'rate' => '10.50',
                    'currency' => 'USD',
                ],
            ],
        ];
    }

    private function purchasedShipmentResponse(): array
    {
        return [
            'id' => 'shp_test_123',
            'tracking_code' => '9400100000000000000000',
            'selected_rate' => [
                'id' => 'rate_usps_123',
                'carrier' => 'USPS',
                'service' => 'Priority',
                'rate' => '8.68',
                'currency' => 'USD',
            ],
            'postage_label' => [
                'label_url' => 'https://example.test/label.png',
                'label_pdf_url' => 'https://example.test/label.pdf',
            ],
            'from_address' => $this->shipment()->fromAddress(),
            'to_address' => $this->shipment()->toAddress(),
            'parcel' => $this->shipment()->parcel(),
        ];
    }
}

final class FakeShippingLabelGateway implements ShippingLabelGateway
{
    public function createShipment(ShippingLabelShipment $shipment): ShippingLabelQuote
    {
        return new ShippingLabelQuote(
            shipmentId: 'fake_shipment',
            fromAddress: $shipment->fromAddress(),
            toAddress: $shipment->toAddress(),
            parcel: $shipment->parcel(),
            rates: [],
            rawResponse: [],
        );
    }

    public function buyShipment(ShippingLabelPurchase $purchase): PurchasedShippingLabel
    {
        return new PurchasedShippingLabel(
            shipmentId: $purchase->shipmentId(),
            rateId: $purchase->rateId(),
            trackingCode: null,
            labelUrl: 'https://example.test/fake-label.pdf',
            carrier: 'USPS',
            service: 'Priority',
            rateAmount: '0.00',
            rateCurrency: 'USD',
            status: ShippingLabelStatus::Purchased,
            fromAddress: [],
            toAddress: [],
            parcel: [],
            rawResponse: [],
        );
    }
}
