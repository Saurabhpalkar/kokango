<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** Listing states of a vendor product (Kokango's own products have status null). */
    public const VENDOR_STATUSES = ['draft', 'pending_review', 'active', 'rejected', 'paused', 'archived'];

    protected $fillable = [
        'category_id',
        'vendor_id',
        'name',
        'slug',
        'hindi_name',
        'description',
        'ingredients',
        'how_to_use',
        'image',
        'badge',
        'is_active',
        'status',
        'review_note',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('price_paise');
    }
}
