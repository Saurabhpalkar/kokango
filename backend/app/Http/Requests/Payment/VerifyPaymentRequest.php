<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_no' => ['required', 'string', 'max:40'],
            'token' => ['nullable', 'string', 'max:100'],
            'gateway_order_id' => ['required', 'string', 'max:100'],
            'gateway_payment_id' => ['required', 'string', 'max:100'],
            'signature' => ['required', 'string', 'max:255'],
        ];
    }
}
