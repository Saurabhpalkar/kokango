<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class PincodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pincode' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return ['pincode.digits' => 'Enter a valid 6 digit pincode.'];
    }
}
