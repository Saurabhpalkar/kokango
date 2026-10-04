<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['sometimes', 'required', 'string', 'max:100'],
            'store_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'store_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'store_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'whatsapp_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'shipping_standard_paise' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000000'],
            'shipping_express_paise' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000000'],
            'free_shipping_above_paise' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000000'],
            'free_shipping_note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gst_percent' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'notification_email' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }
}
