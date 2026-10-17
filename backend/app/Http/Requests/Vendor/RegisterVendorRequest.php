<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

/** "Sell on Kokango" sign-up form. */
class RegisterVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['email', 'phone'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => preg_replace('/\s+/', '', $this->input($key))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:120'],
            'owner_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'city' => ['required', 'string', 'max:100'],
            'gst_no' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Enter a 10-digit mobile number.',
        ];
    }
}
