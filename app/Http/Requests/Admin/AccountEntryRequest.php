<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AccountEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'payer_name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function entryData(): array
    {
        return [
            'title' => (string) $this->string('title'),
            'description' => $this->filled('description') ? (string) $this->string('description') : null,
            'amount' => (float) $this->input('amount'),
            'paid_at' => (string) $this->input('paid_at'),
            'payer_name' => (string) $this->string('payer_name'),
        ];
    }
}