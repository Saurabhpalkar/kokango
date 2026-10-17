<?php

namespace App\Http\Requests\Vendor;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store profile and bank details. Used by the vendor ("Store settings") and by the admin (edit vendor),
 * which may also set a commission override.
 */
class VendorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/\s+/', '', $this->input('phone'))]);
        }
        if (is_string($this->input('email'))) {
            $this->merge(['email' => trim($this->input('email'))]);
        }
        $bank = $this->input('bank');
        if (is_array($bank) && isset($bank['ifsc']) && is_string($bank['ifsc'])) {
            $bank['ifsc'] = strtoupper(trim($bank['ifsc']));
            $this->merge(['bank' => $bank]);
        }
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:120'],
            'owner_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->ignoreUserId())],
            'phone' => ['nullable', 'digits:10'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'digits:6'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'gst_no' => ['nullable', 'string', 'max:20'],
            'pan_no' => ['nullable', 'string', 'max:10'],
            'fssai_no' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
            'bank' => ['nullable', 'array'],
            'bank.account_name' => ['nullable', 'string', 'max:255'],
            'bank.account_no' => ['nullable', 'string', 'max:30'],
            'bank.ifsc' => ['nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank.bank_name' => ['nullable', 'string', 'max:100'],
        ] + ($this->isAdmin() ? ['commission_percent' => ['nullable', 'numeric', 'min:0', 'max:50']] : []);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'bank.ifsc.regex' => 'Enter a valid IFSC code (e.g. HDFC0001234).',
            'commission_percent.max' => 'Commission must be between 0 and 50%.',
        ];
    }

    /** Columns for the vendors table. */
    public function vendorFields(): array
    {
        $d = $this->validated();
        $bank = (array) ($d['bank'] ?? []);

        return [
            'store_name' => $d['store_name'],
            'owner_name' => $d['owner_name'],
            'email' => $d['email'],
            'phone' => $d['phone'] ?? null,
            'city' => $d['city'] ?? null,
            'pincode' => $d['pincode'] ?? null,
            'pickup_address' => $d['pickup_address'] ?? null,
            'gst_no' => isset($d['gst_no']) ? strtoupper($d['gst_no']) : null,
            'pan_no' => isset($d['pan_no']) ? strtoupper($d['pan_no']) : null,
            'fssai_no' => $d['fssai_no'] ?? null,
            'description' => $d['description'] ?? null,
            'bank_account_name' => $bank['account_name'] ?? null,
            'bank_account_no' => $bank['account_no'] ?? null,
            'bank_ifsc' => $bank['ifsc'] ?? null,
            'bank_name' => $bank['bank_name'] ?? null,
        ] + ($this->isAdmin() && $this->exists('commission_percent') ? ['commission_percent' => $d['commission_percent'] ?? null] : []);
    }

    protected function isAdmin(): bool
    {
        return $this->is('api/v1/admin/*');
    }

    /** The user whose own email may be kept (the vendor being edited). */
    protected function ignoreUserId(): ?int
    {
        if ($this->isAdmin()) {
            $id = $this->route('id');

            return $id ? Vendor::whereKey((int) $id)->value('user_id') : null;
        }

        return $this->user()?->id;
    }
}
