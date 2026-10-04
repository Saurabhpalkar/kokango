<?php

namespace App\Http\Resources;

use App\Helpers\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = ImageUrl::resolve($this->image);
        $variants = $this->relationLoaded('variants') ? $this->variants : collect();
        $active = $variants->where('is_active', true);
        $priced = $active->isNotEmpty() ? $active : $variants;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'hindi_name' => $this->hindi_name,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'images' => $imageUrl ? [$imageUrl] : [],
            'badge' => $this->badge,
            'is_active' => (bool) $this->is_active,
            'category' => $this->relationLoaded('category') && $this->category
                ? ['id' => $this->category->id, 'slug' => $this->category->slug, 'name' => $this->category->name]
                : null,
            'min_price_paise' => $priced->isNotEmpty() ? (int) $priced->min('price_paise') : 0,
            'variants' => VariantResource::collection($variants)->resolve(),
        ];
    }
}
