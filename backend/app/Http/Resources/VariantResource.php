<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductVariant */
class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'size_label' => $this->size_label,
            'price_paise' => (int) $this->price_paise,
            'mrp_paise' => $this->mrp_paise === null ? null : (int) $this->mrp_paise,
            'stock' => (int) $this->stock,
            'in_stock' => $this->stock > 0,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
