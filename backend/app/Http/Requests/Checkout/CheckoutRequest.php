<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = Auth::guard('sanctum')->user();
        $guest = $user === null;
        $needsAddressBlock = $guest || ! $this->filled('address_id');

        $rules = [
            'shipping_method' => ['required', Rule::in(['standard', 'express'])],
            'notes' => ['nullable', 'string', 'max:500'],

            'customer' => [$guest ? 'required' : 'nullable', 'array'],
            'customer.name' => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'customer.email' => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
            'customer.phone' => [$guest ? 'required' : 'nullable', 'digits:10'],

            'address' => [Rule::requiredIf($needsAddressBlock), 'nullable', 'array'],
            'address.name' => ['required_with:address', 'nullable', 'string', 'max:120'],
            'address.phone' => ['required_with:address', 'nullable', 'digits:10'],
            'address.line1' => ['required_with:address', 'nullable', 'string', 'max:255'],
            'address.line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'nullable', 'string', 'max:100'],
            'address.state' => ['required_with:address', 'nullable', 'string', 'max:100'],
            'address.pincode' => ['required_with:address', 'nullable', 'digits:6'],
        ];

        $rules['address_id'] = $user
            ? ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $user->id)]
            : ['prohibited'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'customer.phone.digits' => 'Enter a 10 digit mobile number.',
            'address.phone.digits' => 'Enter a 10 digit mobile number.',
            'address.pincode.digits' => 'Enter a valid 6 digit pincode.',
            'address_id.prohibited' => 'Sign in to use a saved address.',
        ];
    }
}
