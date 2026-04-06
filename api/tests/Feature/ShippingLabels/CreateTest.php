<?php

namespace Tests\Feature\ShippingLabels;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelGateway;
use App\Core\Domain\Exceptions\ShippingProviderRequestException;
use App\Core\Domain\ShippingLabel\PurchasedShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;
use App\Core\Domain\ShippingLabel\ShippingLabelQuote;
use App\Core\Domain\ShippingLabel\ShippingLabelShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Doubles\ShippingLabel\FakeShippingLabelGateway;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_persists_a_shipping_label(): void
    {
        $this->app->instance(ShippingLabelGateway::class, new FakeShippingLabelGateway);

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/shipping-labels', $this->payload())
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'id' => 1,
                    'tracking_code' => '9400100000000000000000',
                    'label_url' => 'https://example.test/label.pdf',
                    'carrier' => 'USPS',
                    'service' => 'Priority Express',
                    'rate_amount' => '7.50',
                    'rate_currency' => 'USD',
                    'status' => 'purchased',
                    'from_address' => [
                        'country' => 'US',
                    ],
                    'to_address' => [
                        'country' => 'US',
                    ],
                    'parcel' => [
                        'weight_oz' => 12,
                        'length_in' => 10,
                        'width_in' => 7,
                        'height_in' => 4,
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('easypost_shipment_id', $response->json('data'));
        $this->assertArrayNotHasKey('easypost_rate_id', $response->json('data'));

        $this->assertDatabaseHas('shipping_labels', [
            'id' => 1,
            'user_id' => $user->id,
            'easypost_shipment_id' => 'shp_test_123',
            'easypost_rate_id' => 'rate_usps_express',
            'carrier' => 'USPS',
            'service' => 'Priority Express',
            'status' => 'purchased',
        ]);
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson('/shipping-labels', $this->payload())
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_it_rejects_non_united_states_addresses(): void
    {
        $this->app->instance(ShippingLabelGateway::class, new FakeShippingLabelGateway);

        $user = User::factory()->create();

        $this->actingAs($user);

        $payload = $this->payload();
        $payload['from_address']['country'] = 'BR';
        $payload['to_address']['country'] = 'CA';

        $this->postJson('/shipping-labels', $payload)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'from_address.country' => ['Only addresses in the United States are accepted.'],
                    'to_address.country' => ['Only addresses in the United States are accepted.'],
                ],
            ]);
    }

    public function test_it_translates_provider_request_errors(): void
    {
        $this->app->bind(ShippingLabelGateway::class, fn () => new class implements ShippingLabelGateway
        {
            public function createShipment(ShippingLabelShipment $shipment): ShippingLabelQuote
            {
                throw new ShippingProviderRequestException('The to_address is invalid.');
            }

            public function buyShipment(ShippingLabelPurchase $purchase): PurchasedShippingLabel
            {
                throw new ShippingProviderRequestException('The to_address is invalid.');
            }
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->postJson('/shipping-labels', $this->payload())
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The to_address is invalid.',
            ]);
    }

    private function payload(): array
    {
        return [
            'from_address' => [
                'name' => 'Jane Sender',
                'street1' => '417 Montgomery Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94104',
                'country' => 'US',
            ],
            'to_address' => [
                'name' => 'John Receiver',
                'street1' => '388 Townsend St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94107',
                'country' => 'US',
            ],
            'parcel' => [
                'weight_oz' => 12,
                'length_in' => 10,
                'width_in' => 7,
                'height_in' => 4,
            ],
        ];
    }
}
