<?php

namespace App\Models;

use App\Core\Domain\ShippingLabel\ShippingLabelStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'easypost_shipment_id',
    'easypost_rate_id',
    'tracking_code',
    'label_url',
    'carrier',
    'service',
    'rate_amount',
    'rate_currency',
    'status',
    'from_address_json',
    'to_address_json',
    'parcel_json',
    'raw_response_json',
])]
class ShippingLabel extends Model
{
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where($this->qualifyColumn('user_id'), $userId);
    }

    protected function casts(): array
    {
        return [
            'status' => ShippingLabelStatus::class,
            'from_address_json' => 'array',
            'to_address_json' => 'array',
            'parcel_json' => 'array',
            'raw_response_json' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
