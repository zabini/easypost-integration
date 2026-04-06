<?php

namespace App\Core\Application\ShippingLabel;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;

final readonly class ListShippingLabelsHandler
{
    public function __construct(
        private AuthenticationSession $session,
        private ShippingLabelRepository $repository,
    ) {
    }

    public function handle(ListShippingLabels $command): array
    {
        $userId = $this->session->currentUserId();

        if ($userId === null) {
            throw new AuthenticationRequiredException();
        }

        return $this->repository->findByUserId($userId);
    }
}
