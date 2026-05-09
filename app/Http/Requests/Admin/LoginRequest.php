<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['admin', 'seller'])],
            'remember' => ['nullable', 'boolean'],
        ];
    }
}