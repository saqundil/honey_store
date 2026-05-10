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

$availableLocales = config('app.available_locales', ['en', 'ar']);
$resolvePreferredLocale = static function () use ($availableLocales): string {
    $locale = session('locale', config('app.locale'));

    return in_array($locale, $availableLocales, true)
        ? $locale
        : config('app.fallback_locale', 'en');
};

Route::get('/locale/{locale}', function (string $locale) use ($availableLocales) {
    abort_unless(in_array($locale, $availableLocales, true), 404);
    session(['locale' => $locale]);

    return redirect()->route('home', ['locale' => $locale]);
})->name('locale.switch');

Route::get('/', function () use ($resolvePreferredLocale) {
    return redirect()->route('home', ['locale' => $resolvePreferredLocale()]);
});

Route::get('/about', fn () => redirect()->route('about', ['locale' => $resolvePreferredLocale()]));
Route::get('/contact', fn () => redirect()->route('contact', ['locale' => $resolvePreferredLocale()]));
Route::get('/faq', fn () => redirect()->route('faq', ['locale' => $resolvePreferredLocale()]));
Route::get('/shipping', fn () => redirect()->route('shipping', ['locale' => $resolvePreferredLocale()]));
Route::get('/privacy', fn () => redirect()->route('privacy', ['locale' => $resolvePreferredLocale()]));
Route::get('/terms', fn () => redirect()->route('terms', ['locale' => $resolvePreferredLocale()]));
Route::get('/products', fn () => redirect(route('home', ['locale' => $resolvePreferredLocale()]).'#products'))->name('products.index.redirect');
Route::get('/products/{slug}', fn (string $slug) => redirect()->route('products.show', ['locale' => $resolvePreferredLocale(), 'slug' => $slug]));

Route::prefix('{locale}')
    ->whereIn('locale', $availableLocales)
    ->middleware('locale')
    ->group(function () use ($availableLocales) {
        Route::get('/', function () {
            $products = Product::active()
                ->get()
                ->map(fn (Product $product) => $product->toLocalizedArray())
                ->all();

            return view('pages.home', [
                'products' => $products,
            ]);
        })->name('home');

        Route::get('/products', fn () => redirect(route('home', ['locale' => app()->currentLocale()]).'#products'))->name('products.index');
        Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
        Route::post('/products/{slug}/order', [ProductController::class, 'order'])->name('products.order');

        Route::get('/about', [PageController::class, 'about'])->name('about');
        Route::get('/contact', [PageController::class, 'contact'])->name('contact');
        Route::get('/faq', [PageController::class, 'faq'])->name('faq');
        Route::get('/shipping', [PageController::class, 'shipping'])->name('shipping');
        Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
        Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    });

Route::get('/sitemap.xml', function () {
    $locales = config('app.available_locales', ['en', 'ar']);
    $staticRoutes = collect([
        ['name' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
        ['name' => 'about', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['name' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['name' => 'faq', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['name' => 'shipping', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['name' => 'privacy', 'changefreq' => 'yearly', 'priority' => '0.4'],
        ['name' => 'terms', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ]);

    $urls = collect($locales)
        ->flatMap(fn (string $locale) => $staticRoutes->map(fn (array $route) => [
            'loc' => route($route['name'], ['locale' => $locale]),
            'changefreq' => $route['changefreq'],
            'priority' => $route['priority'],
        ]))
        ->concat(
            Product::active()
                ->get(['slug', 'updated_at'])
                ->flatMap(fn (Product $product) => collect($locales)->map(fn (string $locale) => [
                    'loc' => route('products.show', ['locale' => $locale, 'slug' => $product->slug]),
                    'lastmod' => $product->updated_at?->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.9',
                ]))
        );

    $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

    foreach ($urls as $url) {
        $xml[] = '    <url>';
        $xml[] = '        <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>';

        if (! empty($url['lastmod'])) {
            $xml[] = '        <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1).'</lastmod>';
        }

        $xml[] = '        <changefreq>'.htmlspecialchars($url['changefreq'], ENT_XML1).'</changefreq>';
        $xml[] = '        <priority>'.htmlspecialchars($url['priority'], ENT_XML1).'</priority>';
        $xml[] = '    </url>';
    }

    $xml[] = '</urlset>';

    return response(implode("\n", $xml), 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

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
