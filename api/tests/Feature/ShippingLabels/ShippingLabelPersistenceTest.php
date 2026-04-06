<?php

namespace Tests\Feature\ShippingLabels;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\ShippingLabel\ShippingLabel as DomainShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use App\Models\ShippingLabel;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShippingLabelPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_shipping_labels_table_with_the_expected_schema(): void
    {
        $this->assertTrue(Schema::hasTable('shipping_labels'));
        $this->assertTrue(Schema::hasColumns('shipping_labels', [
            'id',
            'user_id',
            'easypost_shipment_id',
            'easypost_rate_id',
            'tracking_code',
            'label_url',
            'carrier',
            'service',
            'rate_amount',
            'rate_currency',
            'status',
            'from_address_json',
            'to_address_json',
            'parcel_json',
            'raw_response_json',
            'created_at',
            'updated_at',
        ]));

        $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('shipping_labels')"));
        $indexes = collect(DB::select("PRAGMA index_list('shipping_labels')"));

        $this->assertTrue($foreignKeys->contains(
            fn (object $foreignKey) => $foreignKey->table === 'users'
                && $foreignKey->from === 'user_id'
                && $foreignKey->to === 'id',
        ));

        $this->assertTrue($indexes->contains(
            fn (object $index) => str_contains($index->name, 'easypost_shipment_id'),
        ));
    }

    public function test_it_casts_json_fields_and_defines_the_user_relationships(): void
    {
        $user = User::factory()->create();

        $label = ShippingLabel::query()->create($this->shippingLabelAttributes($user->id));

        $label->refresh();
        $user->refresh();

        $this->assertSame('Jane Sender', $label->from_address_json['name']);
        $this->assertSame('John Receiver', $label->to_address_json['name']);
        $this->assertSame(12, $label->parcel_json['weight_oz']);
        $this->assertSame('shipment', $label->raw_response_json['object']);
        $this->assertSame($user->id, $label->user->id);
        $this->assertCount(1, $user->shippingLabels);
        $this->assertTrue($user->shippingLabels->first()->is($label));
    }

    public function test_it_persists_and_maps_shipping_labels_through_the_repository(): void
    {
        $user = User::factory()->create();
        $repository = $this->app->make(ShippingLabelRepository::class);

        $created = $repository->create(new DomainShippingLabel(
            id: null,
            userId: $user->id,
            easypostShipmentId: 'shp_test_123',
            easypostRateId: 'rate_test_123',
            trackingCode: '9400100000000000000000',
            labelUrl: 'https://example.test/label.pdf',
            carrier: 'USPS',
            service: 'Priority',
            rateAmount: '8.68',
            rateCurrency: 'USD',
            status: ShippingLabelStatus::Purchased,
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
                'weight_oz' => 12,
                'length_in' => 10,
                'width_in' => 7,
                'height_in' => 4,
            ],
            rawResponse: [
                'object' => 'shipment',
                'id' => 'shp_test_123',
            ],
        ));

        $this->assertNotNull($created->id());
        $this->assertInstanceOf(DateTimeImmutable::class, $created->createdAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $created->updatedAt());

        $this->assertDatabaseHas('shipping_labels', [
            'id' => $created->id(),
            'user_id' => $user->id,
            'easypost_shipment_id' => 'shp_test_123',
            'easypost_rate_id' => 'rate_test_123',
            'tracking_code' => '9400100000000000000000',
            'carrier' => 'USPS',
            'service' => 'Priority',
            'rate_amount' => '8.68',
            'rate_currency' => 'USD',
            'status' => ShippingLabelStatus::Purchased->value,
        ]);

        $persisted = $repository->findByIdAndUserId($created->id(), $user->id);

        $this->assertNotNull($persisted);
        $this->assertSame($created->id(), $persisted->id());
        $this->assertSame($user->id, $persisted->userId());
        $this->assertSame('shp_test_123', $persisted->easypostShipmentId());
        $this->assertSame('rate_test_123', $persisted->easypostRateId());
        $this->assertSame('9400100000000000000000', $persisted->trackingCode());
        $this->assertSame('https://example.test/label.pdf', $persisted->labelUrl());
        $this->assertSame('USPS', $persisted->carrier());
        $this->assertSame('Priority', $persisted->service());
        $this->assertSame('8.68', $persisted->rateAmount());
        $this->assertSame('USD', $persisted->rateCurrency());
        $this->assertSame(ShippingLabelStatus::Purchased, $persisted->status());
        $this->assertSame('Jane Sender', $persisted->fromAddress()['name']);
        $this->assertSame('John Receiver', $persisted->toAddress()['name']);
        $this->assertSame(12, $persisted->parcel()['weight_oz']);
        $this->assertSame('shipment', $persisted->rawResponse()['object']);
        $this->assertCount(1, $repository->findByUserId($user->id));
    }

    private function shippingLabelAttributes(int $userId): array
    {
        return [
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
        ];
    }
}
