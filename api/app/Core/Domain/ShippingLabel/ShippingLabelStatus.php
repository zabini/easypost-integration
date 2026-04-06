<?php

namespace App\Core\Domain\ShippingLabel;

enum ShippingLabelStatus: string
{
    case Pending = 'pending';
    case Purchased = 'purchased';
    case Failed = 'failed';
}
