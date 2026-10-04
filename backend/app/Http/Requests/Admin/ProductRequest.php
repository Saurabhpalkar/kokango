<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hindi_name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            // https URL, a path served by the frontend ("/images/x.png") or one of our stored files ("products/x.png").
            'image_url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://[^\s]+|/(?!/)[^\s]*|[A-Za-z0-9_./-]+)$#', 'not_regex:#\.\.#'],
            'badge' => ['nullable', 'string', 'max:20'],
            'variants' => ['required', 'array', 'min:1', 'max:50'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => [
                'required', 'string', 'max:100', 'distinct:ignore_case',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $index = explode('.', $attribute)[1] ?? null;
                    $id = $this->input("variants.{$index}.id");

                    $taken = ProductVariant::query()
                        ->where('sku', $value)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($taken) {
                        $fail('The sku has already been taken.');
                    }
                },
            ],
            'variants.*.size_label' => ['required', 'string', 'max:50'],
            // Caps (Rs 10 lakh, 1 million units) keep the money maths far from integer overflow.
            'variants.*.price_paise' => ['required', 'integer', 'min:1', 'max:100000000'],
            'variants.*.mrp_paise' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
