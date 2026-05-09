<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminPanel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use InteractsWithAdminPanel;

    public function index(ReportFilterRequest $request): View|StreamedResponse|RedirectResponse
    {
        $filters = $request->validated();

        $query = $this->filteredOrders($filters);
        $statementOrders = (clone $query)->with(['product', 'product.seller'])->latest();
        $completedOrders = (clone $query)
            ->with(['product.seller'])
            ->where('status', Order::STATUS_COMPLETED)
            ->get();

        $grossSales = (float) $completedOrders->sum('total_price');
        $commissionTotal = (float) $completedOrders->sum(function (Order $order): float {
            return round(((float) $order->total_price) * (((float) $order->product?->seller?->commission_rate) / 100), 2);
        });

        $summary = [
            'orders_count' => (clone $query)->count(),
            'gross_sales' => $grossSales,
            'commission_total' => $commissionTotal,
            'net_earnings' => round($grossSales - $commissionTotal, 2),
        ];

        if (($filters['export'] ?? null) === 'csv') {
            return $this->exportCsv((clone $statementOrders)->get(), $summary);
        }

        return view('admin.reports.index', [
            'orders' => $statementOrders->paginate(20)->withQueryString(),
            'summary' => $summary,
            'filters' => $filters,
            'panelRole' => $this->panelRole(),
            'sellers' => Seller::query()->orderBy('name')->get(),
        ]);
    }

    private function filteredOrders(array $filters): Builder
    {
        $query = $this->scopeOrders(Order::query());

        if (! $this->isSellerPanel() && ! empty($filters['seller_id'])) {
            $sellerId = (int) $filters['seller_id'];

            $query->whereHas('product', function (Builder $productQuery) use ($sellerId): void {
                $productQuery->where('seller_id', $sellerId);
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query;
    }

    private function exportCsv($orders, array $summary): StreamedResponse
    {
        return response()->streamDownload(function () use ($orders, $summary): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Order ID', 'Seller', 'Product', 'Date', 'Amount', 'Status', 'Net']);

            foreach ($orders as $order) {
                $commissionRate = (float) ($order->product?->seller?->commission_rate ?? 0);
                $net = round(((float) $order->total_price) * (1 - ($commissionRate / 100)), 2);

                fputcsv($handle, [
                    $order->id,
                    $order->product?->seller?->name,
                    $order->product?->localized('name'),
                    $order->created_at?->format('Y-m-d H:i'),
                    $order->formattedTotal(),
                    $order->status,
                    $order->formattedAmount($net),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Orders', $summary['orders_count']]);
            fputcsv($handle, ['Gross Sales', $summary['gross_sales']]);
            fputcsv($handle, ['Commission', $summary['commission_total']]);
            fputcsv($handle, ['Net Earnings', $summary['net_earnings']]);

            fclose($handle);
        }, 'seller-statements.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}