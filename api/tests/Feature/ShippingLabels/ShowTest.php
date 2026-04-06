<?php

namespace Tests\Feature\ShippingLabels;

use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use App\Models\ShippingLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_requested_shipping_label_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $shippingLabel = ShippingLabel::query()->create($this->shippingLabelAttributes($user->id, [
            'tracking_code' => '9400100000000000000000',
        ]));

        $this->actingAs($user);

        $response = $this->getJson("/shipping-labels/{$shippingLabel->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $shippingLabel->id,
                    'tracking_code' => '9400100000000000000000',
                    'label_url' => 'https://example.test/label.pdf',
                    'carrier' => 'USPS',
                    'service' => 'Priority',
                    'rate_amount' => '8.68',
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
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('easypost_shipment_id', $response->json('data'));
        $this->assertArrayNotHasKey('easypost_rate_id', $response->json('data'));
    }

    public function test_it_returns_not_found_for_labels_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $shippingLabel = ShippingLabel::query()->create($this->shippingLabelAttributes($anotherUser->id));

        $this->actingAs($user);

        $this->getJson("/shipping-labels/{$shippingLabel->id}")
            ->assertNotFound()
            ->assertJson([
                'message' => 'Shipping label not found.',
            ]);
    }

    public function test_it_requires_authentication_to_get_a_shipping_label(): void
    {
        $this->getJson('/shipping-labels/1')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    private function shippingLabelAttributes(int $userId, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $userId,
            'easypost_shipment_id' => 'shp_test_123',
            'easypost_rate_id' => 'rate_test_123',
            'tracking_code' => '9400100000000000000000',
            'label_url' => 'https://example.test/label.pdf',
            'carrier' => 'USPS',
            'service' => 'Priority',
            'rate_amount' => '8.68',
            'rate_currency' => 'USD',
            'status' => ShippingLabelStatus::Purchased,
            'from_address_json' => [
                'name' => 'Jane Sender',
                'street1' => '417 Montgomery Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94104',
                'country' => 'US',
            ],
            'to_address_json' => [
                'name' => 'John Receiver',
                'street1' => '388 Townsend St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'zip' => '94107',
                'country' => 'US',
            ],
            'parcel_json' => [
                'weight_oz' => 12,
                'length_in' => 10,
                'width_in' => 7,
                'height_in' => 4,
            ],
            'raw_response_json' => [
                'object' => 'shipment',
                'id' => 'shp_test_123',
            ],
        ], $overrides);
    }
}
