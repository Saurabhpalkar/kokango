<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_no' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:190'],
        ];
    }
}
