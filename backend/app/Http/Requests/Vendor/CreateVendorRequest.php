<?php

namespace App\Http\Requests\Vendor;

/** Admin creates a vendor account (profile + login password, optionally approved at once). */
class CreateVendorRequest extends VendorProfileRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'phone' => ['required', 'digits:10'],
            'city' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'approve_now' => ['nullable', 'boolean'],
        ]);
    }
}
