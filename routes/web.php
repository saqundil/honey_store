<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, config('app.available_locales', ['en', 'ar']), true), 404);

    session(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');

Route::view('/', 'pages.home')->name('home');
Route::redirect('/products', '/#products')->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{slug}/order', [ProductController::class, 'order'])->name('products.order');
