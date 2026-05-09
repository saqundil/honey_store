<?php

namespace App\Http\Requests\Admin;

use App\Models\Seller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Seller|null $seller */
        $seller = $this->route('seller');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:120', Rule::unique('sellers', 'email')->ignore($seller?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => [$seller ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'balance' => ['nullable', 'numeric', 'min:0'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function sellerData(): array
    {
        $data = [
            'name' => (string) $this->string('name'),
            'email' => (string) $this->string('email'),
            'phone' => (string) $this->input('phone', ''),
            'balance' => (float) $this->input('balance', 0),
            'commission_rate' => (float) $this->input('commission_rate', 10),
        ];

        if ($this->filled('password')) {
            $data['password'] = (string) $this->string('password');
        }

        return $data;
    }
}