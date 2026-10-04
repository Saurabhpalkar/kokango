<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock' => ['required_without:change', 'nullable', 'integer', 'min:0', 'max:1000000'],
            'change' => ['required_without:stock', 'nullable', 'integer', 'between:-1000000,1000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
