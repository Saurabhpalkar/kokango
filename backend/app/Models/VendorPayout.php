<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Money Kokango sends to a seller for delivered, unsettled orders. */
class VendorPayout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }
}
