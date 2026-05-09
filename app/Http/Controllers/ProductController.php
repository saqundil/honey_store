<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();
        $relatedProducts = Product::active()
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get()
            ->map(fn (Product $relatedProduct) => $relatedProduct->toLocalizedArray())
            ->all();

        return view('pages.product-show', [
            'product' => $product->toLocalizedArray(),
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function order(Request $request, string $slug): RedirectResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::create([
            'product_id' => $product->id,
            'customer_name' => $validated['customer_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
            'locale' => app()->getLocale(),
            'unit_price' => $product->price_value,
            'total_price' => (float) $product->price_value * (int) $validated['quantity'],
            'currency' => $product->currency,
            'currency_position' => $product->currency_position,
            'price_decimals' => $product->price_decimals,
            'status' => 'pending',
        ]);

        $product->load('seller');
        $product->seller?->refreshBalance();

        $productData = $product->toLocalizedArray();

        Log::info('Product order request received.', [
            'order_id' => $order->id,
            'product' => $product->slug,
            'locale' => app()->getLocale(),
            'order' => $validated,
        ]);

        return redirect()
            ->route('products.show', ['slug' => $slug])
            ->with('order_success', __('home.product_page.order_success', ['product' => $productData['name']]));
    }
}