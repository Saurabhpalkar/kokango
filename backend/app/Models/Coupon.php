<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A discount code customers enter at checkout. */
class Coupon extends Model
{
    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_subtotal_paise', 'max_discount_paise',
        'starts_at', 'ends_at', 'usage_limit', 'once_per_customer', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_subtotal_paise' => 'integer',
            'max_discount_paise' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'once_per_customer' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Codes are stored and compared in upper case. */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }
}
