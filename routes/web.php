<?php

use App\Http\Controllers\Admin\AccountEntryController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SellerController;
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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');

    Route::middleware('panel.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/toggle-status', [AdminProductController::class, 'toggleStatus'])->name('products.toggle-status');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::middleware('panel.role:admin')->group(function () {
            Route::get('/accounts', [AccountEntryController::class, 'index'])->name('accounts.index');
            Route::post('/accounts', [AccountEntryController::class, 'store'])->name('accounts.store');
            Route::delete('/accounts/{accountEntry}', [AccountEntryController::class, 'destroy'])->name('accounts.destroy');

            Route::get('/sellers', [SellerController::class, 'index'])->name('sellers.index');
            Route::get('/sellers/create', [SellerController::class, 'create'])->name('sellers.create');
            Route::post('/sellers', [SellerController::class, 'store'])->name('sellers.store');
            Route::get('/sellers/{seller}', [SellerController::class, 'show'])->name('sellers.show');
            Route::get('/sellers/{seller}/edit', [SellerController::class, 'edit'])->name('sellers.edit');
            Route::put('/sellers/{seller}', [SellerController::class, 'update'])->name('sellers.update');
            Route::delete('/sellers/{seller}', [SellerController::class, 'destroy'])->name('sellers.destroy');
        });
    });
});
