<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A seller on the Kokango marketplace. Logs in through its User (role "vendor"). */
class Vendor extends Model
{
    public const STATUSES = ['pending', 'approved', 'suspended', 'rejected'];

    protected $fillable = [
        'user_id',
        'store_name',
        'owner_name',
        'email',
        'phone',
        'city',
        'pincode',
        'pickup_address',
        'gst_no',
        'pan_no',
        'fssai_no',
        'description',
        'status',
        'status_reason',
        'commission_percent',
        'bank_account_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
