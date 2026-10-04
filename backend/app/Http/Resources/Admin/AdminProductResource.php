<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

/** @mixin \App\Models\Product */
class AdminProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $variants = $this->relationLoaded('variants') ? $this->variants : collect();

        $data['stock_total'] = (int) $variants->sum('stock');

        return $data;
    }
}
