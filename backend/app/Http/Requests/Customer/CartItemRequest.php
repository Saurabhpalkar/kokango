<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            // Generous cap only to keep absurd numbers away from the stock/limit messages in CartService.
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
