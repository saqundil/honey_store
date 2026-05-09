<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminPanel;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    use InteractsWithAdminPanel;

    public function index(): View
    {
        $productQuery = $this->scopeProducts(Product::query());
        $orderQuery = $this->scopeOrders(Order::query()->with(['product', 'product.seller']));

        $stats = [
            'orders_count' => (clone $orderQuery)->count(),
            'total_sales' => (float) (clone $orderQuery)
                ->where('status', Order::STATUS_COMPLETED)
                ->sum('total_price'),
            'products_count' => (clone $productQuery)->count(),
            'pending_orders' => (clone $orderQuery)
                ->where('status', Order::STATUS_PENDING)
                ->count(),
        ];

        $recentOrders = (clone $orderQuery)
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'monthlySales' => $this->monthlySales((clone $orderQuery)->where('status', Order::STATUS_COMPLETED)),
            'recentOrders' => $recentOrders,
            'panelRole' => $this->panelRole(),
        ]);
    }

    private function monthlySales(Builder $query): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));

        $totals = (clone $query)
            ->where('created_at', '>=', $months->first()->copy()->startOfMonth())
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_price) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->year, $row->month));

        return [
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->all(),
            'values' => $months->map(fn ($month) => (float) ($totals->get($month->format('Y-m'))->total ?? 0))->all(),
        ];
    }
}