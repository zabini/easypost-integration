<?php

namespace App\Core\Domain\Contracts\ShippingLabel;

use App\Core\Domain\ShippingLabel\ShippingLabel;

interface ShippingLabelRepository
{
    public function create(ShippingLabel $shippingLabel): ShippingLabel;

    public function findByIdAndUserId(int $id, int $userId): ?ShippingLabel;

    /**
     * @return list<ShippingLabel>
     */
    public function findByUserId(int $userId): array;
}
