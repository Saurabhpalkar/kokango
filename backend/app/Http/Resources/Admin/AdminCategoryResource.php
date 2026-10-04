<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

/** @mixin \App\Models\Category */
class AdminCategoryResource extends CategoryResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
