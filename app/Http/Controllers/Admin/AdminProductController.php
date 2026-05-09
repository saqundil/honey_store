<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminPanel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminProductController extends Controller
{
    use InteractsWithAdminPanel;

    public function index(Request $request): View
    {
        $products = $this->scopeProducts(Product::query()->with('seller'))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';

                $query->where(function ($productQuery) use ($search): void {
                    $productQuery
                        ->where('slug', 'like', $search)
                        ->orWhere('sku', 'like', $search)
                        ->orWhere('translations->en->name', 'like', $search)
                        ->orWhere('translations->ar->name', 'like', $search);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->when(! $this->isSellerPanel() && $request->filled('seller_id'), function ($query) use ($request): void {
                $query->where('seller_id', (int) $request->input('seller_id'));
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'sellers' => Seller::query()->orderBy('name')->get(),
            'panelRole' => $this->panelRole(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product([
                'currency' => '$',
                'currency_position' => 'prefix',
                'price_decimals' => 2,
                'sort_order' => (Product::max('sort_order') ?? 0) + 1,
                'is_active' => true,
                'translations' => [
                    'en' => ['name' => '', 'excerpt' => '', 'description' => ''],
                    'ar' => ['name' => '', 'excerpt' => '', 'description' => ''],
                ],
            ]),
            'sellers' => Seller::query()->orderBy('name')->get(),
            'panelRole' => $this->panelRole(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product();

        $this->persistProduct($product, $request);

        return redirect()
            ->route('admin.products.index')
            ->with('status', app()->isLocale('ar') ? 'تم إنشاء المنتج بنجاح.' : 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $this->ensureProductAccess($product);

        return view('admin.products.edit', [
            'product' => $product,
            'sellers' => Seller::query()->orderBy('name')->get(),
            'panelRole' => $this->panelRole(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureProductAccess($product);
        $this->persistProduct($product, $request);

        return redirect()
            ->route('admin.products.index')
            ->with('status', app()->isLocale('ar') ? 'تم تحديث المنتج بنجاح.' : 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureProductAccess($product);
        $this->deleteStoredImage($product->image);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', app()->isLocale('ar') ? 'تم حذف المنتج.' : 'Product deleted successfully.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $this->ensureProductAccess($product);
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', app()->isLocale('ar') ? 'تم تحديث حالة المنتج.' : 'Product status updated.');
    }

    private function persistProduct(Product $product, ProductRequest $request): void
    {
        $data = $request->baseData();
        $data['seller_id'] = $this->selectedSellerId($request->input('seller_id'));

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($product->image);
            $data['image'] = 'storage/'.$request->file('image')->store('products', 'public');
        }

        $translations = $product->translations ?? [];

        foreach ($request->translationData() as $locale => $localeData) {
            $translations[$locale] = array_replace($translations[$locale] ?? [], $localeData);
        }

        $data['translations'] = $translations;

        $product->fill($data)->save();
    }

    private function deleteStoredImage(?string $imagePath): void
    {
        if (! $imagePath || ! str_starts_with($imagePath, 'storage/')) {
            return;
        }

        $storagePath = substr($imagePath, strlen('storage/'));

        if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }
    }
}