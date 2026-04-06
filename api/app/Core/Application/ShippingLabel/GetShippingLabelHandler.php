<?php

namespace App\Core\Application\ShippingLabel;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\Exceptions\ShippingLabelNotFoundException;
use App\Core\Domain\ShippingLabel\ShippingLabel;

final readonly class GetShippingLabelHandler
{
    public function __construct(
        private AuthenticationSession $session,
        private ShippingLabelRepository $repository,
    ) {
    }

    public function handle(GetShippingLabel $command): ShippingLabel
    {
        $userId = $this->session->currentUserId();

        if ($userId === null) {
            throw new AuthenticationRequiredException();
        }

        $shippingLabel = $this->repository->findByIdAndUserId($command->id, $userId);

        if ($shippingLabel === null) {
            throw new ShippingLabelNotFoundException();
        }

        return $shippingLabel;
    }
}
