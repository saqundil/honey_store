<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = $this->findProduct($slug);

        abort_unless($product !== null, 404);

        return view('pages.product-show', [
            'product' => $product,
        ]);
    }

    public function order(Request $request, string $slug): RedirectResponse
    {
        $product = $this->findProduct($slug);

        abort_unless($product !== null, 404);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Log::info('Product order request received.', [
            'product' => $product['slug'],
            'locale' => app()->getLocale(),
            'order' => $validated,
        ]);

        return redirect()
            ->route('products.show', ['slug' => $slug])
            ->with('order_success', __('home.product_page.order_success', ['product' => $product['name']]));
    }

    private function findProduct(string $slug): ?array
    {
        $products = trans('home.products.items');

        foreach ($products as $product) {
            if (($product['slug'] ?? null) === $slug) {
                return $product;
            }
        }

        return null;
    }
}