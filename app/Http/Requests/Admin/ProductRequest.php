<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $requiresSeller = Auth::guard('web')->check();

        return [
            'seller_id' => [$requiresSeller ? 'required' : 'nullable', 'exists:sellers,id'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('products', 'slug')->ignore($product?->id)],
            'sku' => ['required', 'string', 'max:120', Rule::unique('products', 'sku')->ignore($product?->id)],
            'price_value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:20'],
            'currency_position' => ['required', Rule::in(['prefix', 'suffix'])],
            'price_decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'image' => [Rule::requiredIf($product === null), 'image', 'max:4096'],
            'en_name' => ['required', 'string', 'max:255'],
            'en_excerpt' => ['nullable', 'string', 'max:500'],
            'en_description' => ['nullable', 'string'],
            'ar_name' => ['required', 'string', 'max:255'],
            'ar_excerpt' => ['nullable', 'string', 'max:500'],
            'ar_description' => ['nullable', 'string'],
        ];
    }

    public function baseData(): array
    {
        return [
            'slug' => (string) $this->string('slug'),
            'sku' => (string) $this->string('sku'),
            'price_value' => (float) $this->input('price_value'),
            'currency' => (string) $this->string('currency'),
            'currency_position' => (string) $this->string('currency_position'),
            'price_decimals' => (int) $this->input('price_decimals'),
            'sort_order' => (int) $this->input('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    public function translationData(): array
    {
        return [
            'en' => [
                'name' => (string) $this->string('en_name'),
                'excerpt' => (string) $this->input('en_excerpt', ''),
                'description' => (string) $this->input('en_description', ''),
            ],
            'ar' => [
                'name' => (string) $this->string('ar_name'),
                'excerpt' => (string) $this->input('ar_excerpt', ''),
                'description' => (string) $this->input('ar_description', ''),
            ],
        ];
    }
}