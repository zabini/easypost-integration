<?php

namespace Tests\Feature\ShippingLabels;

use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use App\Models\ShippingLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_shipping_labels_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $firstLabel = ShippingLabel::query()->create($this->shippingLabelAttributes($user->id, [
            'easypost_shipment_id' => 'shp_test_123',
            'tracking_code' => '9400100000000000000000',
        ]));
        $secondLabel = ShippingLabel::query()->create($this->shippingLabelAttributes($user->id, [
            'easypost_shipment_id' => 'shp_test_456',
            'tracking_code' => '9400100000000000000001',
        ]));
        $anotherUsersLabel = ShippingLabel::query()->create($this->shippingLabelAttributes($anotherUser->id, [
            'easypost_shipment_id' => 'shp_test_789',
            'tracking_code' => '9400100000000000000002',
        ]));

        $this->actingAs($user);

        $response = $this->getJson('/shipping-labels')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $firstLabel->id,
                'easypost_shipment_id' => 'shp_test_123',
            ])
            ->assertJsonFragment([
                'id' => $secondLabel->id,
                'easypost_shipment_id' => 'shp_test_456',
            ]);

        $returnedIds = collect($response->json('data'))
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([$firstLabel->id, $secondLabel->id], $returnedIds);
        $this->assertNotContains($anotherUsersLabel->id, $returnedIds);
    }

    public function test_it_requires_authentication_to_list_shipping_labels(): void
    {
        $this->getJson('/shipping-labels')
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
