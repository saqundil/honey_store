<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminPanel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    use InteractsWithAdminPanel;

    public function index(Request $request): View
    {
        $orders = $this->scopeOrders(Order::query()->with(['product', 'product.seller']))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';

                $query->where(function ($orderQuery) use ($search): void {
                    $orderQuery
                        ->where('customer_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('start_date')))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('end_date')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => Order::statuses(),
            'panelRole' => $this->panelRole(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['product', 'product.seller']);
        $this->ensureOrderAccess($order);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => Order::statuses(),
            'panelRole' => $this->panelRole(),
        ]);
    }

    public function updateStatus(OrderStatusRequest $request, Order $order): RedirectResponse
    {
        $order->load(['product', 'product.seller']);
        $this->ensureOrderAccess($order);

        $order->update([
            'status' => $request->validated()['status'],
        ]);

        $order->product?->seller?->refreshBalance();

        return back()->with('status', app()->isLocale('ar') ? 'تم تحديث حالة الطلب.' : 'Order status updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->load(['product', 'product.seller']);
        $this->ensureOrderAccess($order);

        $seller = $order->product?->seller;
        $order->delete();
        $seller?->refreshBalance();

        return redirect()
            ->route('admin.orders.index')
            ->with('status', app()->isLocale('ar') ? 'تم حذف الطلب.' : 'Order deleted successfully.');
    }
}