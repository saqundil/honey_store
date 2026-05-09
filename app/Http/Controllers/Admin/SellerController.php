<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SellerRequest;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function index(): View
    {
        $sellers = Seller::query()
            ->withCount('products')
            ->withCount(['orders as orders_count'])
            ->withSum([
                'orders as completed_sales' => fn ($query) => $query->where('status', Order::STATUS_COMPLETED),
            ], 'total_price')
            ->latest()
            ->paginate(12);

        return view('admin.sellers.index', [
            'sellers' => $sellers,
            'panelRole' => 'admin',
        ]);
    }

    public function create(): View
    {
        return view('admin.sellers.create', [
            'seller' => new Seller([
                'balance' => 0,
                'commission_rate' => 10,
            ]),
            'panelRole' => 'admin',
        ]);
    }

    public function store(SellerRequest $request): RedirectResponse
    {
        Seller::query()->create($request->sellerData());

        return redirect()
            ->route('admin.sellers.index')
            ->with('status', app()->isLocale('ar') ? 'تم إنشاء البائع.' : 'Seller created successfully.');
    }

    public function show(Seller $seller): View
    {
        $products = $seller->products()->latest()->take(8)->get();
        $orders = $seller->orders()->with('product')->latest()->paginate(10);
        $grossSales = $seller->grossSales();

        return view('admin.sellers.show', [
            'seller' => $seller,
            'products' => $products,
            'orders' => $orders,
            'grossSales' => $grossSales,
            'netEarnings' => $seller->netEarnings($grossSales),
            'panelRole' => 'admin',
        ]);
    }

    public function edit(Seller $seller): View
    {
        return view('admin.sellers.edit', [
            'seller' => $seller,
            'panelRole' => 'admin',
        ]);
    }

    public function update(SellerRequest $request, Seller $seller): RedirectResponse
    {
        $seller->update($request->sellerData());
        $seller->refreshBalance();

        return redirect()
            ->route('admin.sellers.index')
            ->with('status', app()->isLocale('ar') ? 'تم تحديث بيانات البائع.' : 'Seller updated successfully.');
    }

    public function destroy(Seller $seller): RedirectResponse
    {
        if ($seller->products()->exists() || $seller->orders()->exists()) {
            return back()->withErrors([
                'seller' => app()->isLocale('ar')
                    ? 'لا يمكن حذف البائع قبل نقل أو حذف منتجاته وطلباته.'
                    : 'This seller cannot be deleted while products or orders are still linked to the account.',
            ]);
        }

        $seller->delete();

        return redirect()
            ->route('admin.sellers.index')
            ->with('status', app()->isLocale('ar') ? 'تم حذف البائع.' : 'Seller deleted successfully.');
    }
}