<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, config('app.available_locales', ['en', 'ar']), true), 404);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

Route::get('/', function () {
    $products = Product::active()
        ->get()
        ->map(fn (Product $product) => $product->toLocalizedArray())
        ->all();

    return view('pages.home', [
        'products' => $products,
    ]);
})->name('home');

Route::redirect('/products', '/#products')->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{slug}/order', [ProductController::class, 'order'])->name('products.order');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/shipping', [PageController::class, 'shipping'])->name('shipping');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
