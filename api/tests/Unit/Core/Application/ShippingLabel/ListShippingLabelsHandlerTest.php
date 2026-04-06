<?php

namespace Tests\Unit\Core\Application\ShippingLabel;

use App\Core\Application\ShippingLabel\ListShippingLabels;
use App\Core\Application\ShippingLabel\ListShippingLabelsHandler;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\ShippingLabel\ShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Auth\FakeAuthenticationSession;
use Tests\Doubles\ShippingLabel\InMemoryShippingLabelRepository;

class ListShippingLabelsHandlerTest extends TestCase
{
    public function test_it_lists_only_the_authenticated_users_shipping_labels(): void
    {
        $repository = new InMemoryShippingLabelRepository;
        $repository->create($this->shippingLabel(userId: 7, easypostShipmentId: 'shp_test_123'));
        $repository->create($this->shippingLabel(userId: 8, easypostShipmentId: 'shp_test_456'));

        $handler = new ListShippingLabelsHandler(
            new FakeAuthenticationSession(7),
            $repository,
        );

        $shippingLabels = $handler->handle(new ListShippingLabels());

        $this->assertCount(1, $shippingLabels);
        $this->assertSame(7, $shippingLabels[0]->userId());
        $this->assertSame('shp_test_123', $shippingLabels[0]->easypostShipmentId());
    }

    public function test_it_requires_an_authenticated_user(): void
    {
        $handler = new ListShippingLabelsHandler(
            new FakeAuthenticationSession(),
            new InMemoryShippingLabelRepository,
        );

        $this->expectException(AuthenticationRequiredException::class);

        $handler->handle(new ListShippingLabels());
    }

    private function shippingLabel(int $userId, string $easypostShipmentId): ShippingLabel
    {
        return new ShippingLabel(
            id: null,
            userId: $userId,
            easypostShipmentId: $easypostShipmentId,
            easypostRateId: 'rate_test_123',
            trackingCode: '9400100000000000000000',
            labelUrl: 'https://example.test/label.pdf',
            carrier: 'USPS',
            service: 'Priority',
            rateAmount: '8.68',
            rateCurrency: 'USD',
            status: ShippingLabelStatus::Purchased,
            fromAddress: [
                'country' => 'US',
            ],
            toAddress: [
                'country' => 'US',
            ],
            parcel: [
                'weight_oz' => 12,
            ],
            rawResponse: [
                'object' => 'shipment',
                'id' => $easypostShipmentId,
            ],
        );
    }
}
