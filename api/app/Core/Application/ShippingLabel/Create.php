<?php

namespace App\Core\Application\ShippingLabel;

final readonly class Create
{
    public function __construct(
        public array $fromAddress,
        public array $toAddress,
        public array $parcel,
    ) {}
}
