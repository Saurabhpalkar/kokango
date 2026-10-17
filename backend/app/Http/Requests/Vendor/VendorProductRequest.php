<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A vendor's product listing with its pack sizes. */
class VendorProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            // A "data:image/...;base64," upload from the form (max ~2 MB file), or the URL the form received.
            'image_url' => ['nullable', 'string', 'max:3000000'],
            'status' => ['required', Rule::in(['draft', 'pending_review'])],
            'variants' => ['required', 'array', 'min:1', 'max:10'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size_label' => ['required', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:64'],
            'variants.*.price_paise' => ['required', 'integer', 'min:1', 'max:100000000'],
            'variants.*.mrp_paise' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required' => 'Add at least one pack size.',
            'variants.*.size_label.required' => 'Each size needs a name.',
            'variants.*.price_paise.min' => 'Each size needs a price above zero.',
        ];
    }
}
