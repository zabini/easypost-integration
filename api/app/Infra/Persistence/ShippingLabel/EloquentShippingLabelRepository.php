<?php

namespace App\Infra\Persistence\ShippingLabel;

use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Core\Domain\ShippingLabel\ShippingLabel;
use App\Models\ShippingLabel as EloquentShippingLabel;

final readonly class EloquentShippingLabelRepository implements ShippingLabelRepository
{
    public function __construct(private ShippingLabelMapper $mapper)
    {
    }

    public function create(ShippingLabel $shippingLabel): ShippingLabel
    {
        $label = EloquentShippingLabel::query()->create(
            $this->mapper->toEloquentAttributes($shippingLabel),
        );

        return $this->mapper->toDomain($label);
    }

    public function findByIdAndUserId(int $id, int $userId): ?ShippingLabel
    {
        $label = EloquentShippingLabel::query()
            ->ownedBy($userId)
            ->whereKey($id)
            ->first();

        if ($label === null) {
            return null;
        }

        return $this->mapper->toDomain($label);
    }

    public function findByUserId(int $userId): array
    {
        return EloquentShippingLabel::query()
            ->ownedBy($userId)
            ->latest()
            ->get()
            ->map(fn (EloquentShippingLabel $label) => $this->mapper->toDomain($label))
            ->all();
    }
}
