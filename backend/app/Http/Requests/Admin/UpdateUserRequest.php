<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'role' => ['sometimes', 'required', 'string', 'in:admin,customer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
