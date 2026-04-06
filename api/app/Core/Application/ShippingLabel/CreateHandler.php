<?php

namespace App\Core\Application\ShippingLabel;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelGateway;
use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\Exceptions\ShippingLabelUspsRateUnavailableException;
use App\Core\Domain\ShippingLabel\ShippingLabel;
use App\Core\Domain\ShippingLabel\ShippingLabelFactory;
use App\Core\Domain\ShippingLabel\ShippingLabelPurchase;

final readonly class CreateHandler
{
    public function __construct(
        private AuthenticationSession $session,
        private ShippingLabelGateway $gateway,
        private ShippingLabelRepository $repository,
        private ShippingLabelFactory $factory,
    ) {}

    public function handle(Create $command): ShippingLabel
    {
        $userId = $this->session->currentUserId();

        if ($userId === null) {
            throw new AuthenticationRequiredException;
        }

        $shipment = $this->factory->createShipment(
            fromAddress: $command->fromAddress,
            toAddress: $command->toAddress,
            parcel: $command->parcel,
        );

        $quote = $this->gateway->createShipment($shipment);
        $selectedRate = $quote->findLowestRateByCarrier('USPS');

        if ($selectedRate === null) {
            throw new ShippingLabelUspsRateUnavailableException;
        }

        $purchasedLabel = $this->gateway->buyShipment(new ShippingLabelPurchase(
            shipmentId: $quote->shipmentId(),
            rateId: $selectedRate->id(),
        ));

        return $this->repository->create(
            $this->factory->createPersistedLabel(
                userId: $userId,
                purchasedLabel: $purchasedLabel,
            ),
        );
    }
}
