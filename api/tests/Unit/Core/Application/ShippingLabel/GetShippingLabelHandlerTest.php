<?php

namespace Tests\Unit\Core\Application\ShippingLabel;

use App\Core\Application\ShippingLabel\GetShippingLabel;
use App\Core\Application\ShippingLabel\GetShippingLabelHandler;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\Exceptions\ShippingLabelNotFoundException;
use App\Core\Domain\ShippingLabel\ShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Auth\FakeAuthenticationSession;
use Tests\Doubles\ShippingLabel\InMemoryShippingLabelRepository;

class GetShippingLabelHandlerTest extends TestCase
{
    public function test_it_returns_the_authenticated_users_shipping_label(): void
    {
        $repository = new InMemoryShippingLabelRepository;
        $shippingLabel = $repository->create($this->shippingLabel(userId: 7, easypostShipmentId: 'shp_test_123'));

        $handler = new GetShippingLabelHandler(
            new FakeAuthenticationSession(7),
            $repository,
        );

        $returnedShippingLabel = $handler->handle(new GetShippingLabel($shippingLabel->id()));

        $this->assertSame($shippingLabel->id(), $returnedShippingLabel->id());
        $this->assertSame(7, $returnedShippingLabel->userId());
    }

    public function test_it_requires_an_authenticated_user(): void
    {
        $handler = new GetShippingLabelHandler(
            new FakeAuthenticationSession(),
            new InMemoryShippingLabelRepository,
        );

        $this->expectException(AuthenticationRequiredException::class);

        $handler->handle(new GetShippingLabel(1));
    }

    public function test_it_throws_when_the_shipping_label_is_not_found(): void
    {
        $handler = new GetShippingLabelHandler(
            new FakeAuthenticationSession(7),
            new InMemoryShippingLabelRepository,
        );

        $this->expectException(ShippingLabelNotFoundException::class);

        $handler->handle(new GetShippingLabel(1));
    }

    public function test_it_does_not_return_another_users_shipping_label(): void
    {
        $repository = new InMemoryShippingLabelRepository;
        $shippingLabel = $repository->create($this->shippingLabel(userId: 8, easypostShipmentId: 'shp_test_123'));

        $handler = new GetShippingLabelHandler(
            new FakeAuthenticationSession(7),
            $repository,
        );

        $this->expectException(ShippingLabelNotFoundException::class);

        $handler->handle(new GetShippingLabel($shippingLabel->id()));
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
