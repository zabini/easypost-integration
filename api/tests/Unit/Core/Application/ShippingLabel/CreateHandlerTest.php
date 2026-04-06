<?php

namespace Tests\Unit\Core\Application\ShippingLabel;

use App\Core\Application\ShippingLabel\Create;
use App\Core\Application\ShippingLabel\CreateHandler;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\Exceptions\ShippingLabelAddressNotSupportedException;
use App\Core\Domain\Exceptions\ShippingLabelUspsRateUnavailableException;
use App\Core\Domain\ShippingLabel\ShippingLabelFactory;
use App\Core\Domain\ShippingLabel\ShippingLabelRate;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Auth\FakeAuthenticationSession;
use Tests\Doubles\ShippingLabel\FakeShippingLabelGateway;
use Tests\Doubles\ShippingLabel\InMemoryShippingLabelRepository;

class CreateHandlerTest extends TestCase
{
    public function test_it_creates_a_usps_shipping_label_for_the_authenticated_user(): void
    {
        $session = new FakeAuthenticationSession(7);
        $gateway = new FakeShippingLabelGateway;
        $repository = new InMemoryShippingLabelRepository;
        $handler = new CreateHandler($session, $gateway, $repository, new ShippingLabelFactory);

        $shippingLabel = $handler->handle(new Create(
            fromAddress: [
                'name' => 'Jane Sender',
                'street1' => '417 Montgomery Street',
                'city' => 'San Francisco',
                'state' => 'ca',
                'zip' => '94104',
                'country' => 'usa',
            ],
            toAddress: [
                'name' => 'John Receiver',
                'street1' => '388 Townsend St',
                'city' => 'San Francisco',
                'state' => 'ca',
                'zip' => '94107',
                'country' => 'us',
            ],
            parcel: [
                'weight_oz' => 12,
                'length_in' => 10,
                'width_in' => 7,
                'height_in' => 4,
            ],
        ));

        $this->assertSame(1, $shippingLabel->id());
        $this->assertSame(7, $shippingLabel->userId());
        $this->assertSame('rate_usps_express', $shippingLabel->easypostRateId());
        $this->assertSame('US', $shippingLabel->fromAddress()['country']);
        $this->assertSame('US', $shippingLabel->toAddress()['country']);
        $this->assertSame(12.0, $shippingLabel->parcel()['weight_oz']);
        $this->assertSame('rate_usps_express', $gateway->purchase?->rateId());
        $this->assertSame(1, count($repository->findByUserId(7)));
    }

    public function test_it_requires_an_authenticated_user(): void
    {
        $handler = new CreateHandler(
            new FakeAuthenticationSession,
            new FakeShippingLabelGateway,
            new InMemoryShippingLabelRepository,
            new ShippingLabelFactory,
        );

        $this->expectException(AuthenticationRequiredException::class);

        $handler->handle(new Create(
            fromAddress: $this->address(),
            toAddress: $this->address(),
            parcel: $this->parcel(),
        ));
    }

    public function test_it_rejects_addresses_outside_the_united_states(): void
    {
        $handler = new CreateHandler(
            new FakeAuthenticationSession(7),
            new FakeShippingLabelGateway,
            new InMemoryShippingLabelRepository,
            new ShippingLabelFactory,
        );

        $this->expectException(ShippingLabelAddressNotSupportedException::class);

        $handler->handle(new Create(
            fromAddress: array_merge($this->address(), ['country' => 'BR']),
            toAddress: $this->address(),
            parcel: $this->parcel(),
        ));
    }

    public function test_it_fails_when_no_usps_rate_is_available(): void
    {
        $handler = new CreateHandler(
            new FakeAuthenticationSession(7),
            new FakeShippingLabelGateway([
                new ShippingLabelRate(
                    id: 'rate_ups_ground',
                    carrier: 'UPS',
                    service: 'Ground',
                    rateAmount: '11.20',
                    rateCurrency: 'USD',
                ),
            ]),
            new InMemoryShippingLabelRepository,
            new ShippingLabelFactory,
        );

        $this->expectException(ShippingLabelUspsRateUnavailableException::class);

        $handler->handle(new Create(
            fromAddress: $this->address(),
            toAddress: $this->address(),
            parcel: $this->parcel(),
        ));
    }

    private function address(): array
    {
        return [
            'name' => 'Jane Sender',
            'street1' => '417 Montgomery Street',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip' => '94104',
            'country' => 'US',
        ];
    }

    private function parcel(): array
    {
        return [
            'weight_oz' => 12,
            'length_in' => 10,
            'width_in' => 7,
            'height_in' => 4,
        ];
    }
}
