<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal_paise' => 'integer',
            'shipping_paise' => 'integer',
            'tax_paise' => 'integer',
            'discount_paise' => 'integer',
            'total_paise' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    /** Order number format: KKG-{year}-{id padded to 6}. */
    public static function makeOrderNo(int $id, ?int $year = null): string
    {
        return sprintf('KKG-%d-%06d', $year ?? (int) now()->format('Y'), $id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** True when the customer chose Cash on Delivery (the payment record's gateway is "cod"). */
    public function isCod(): bool
    {
        return Payment::where('order_id', $this->id)->where('gateway', 'cod')->exists();
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }
}
