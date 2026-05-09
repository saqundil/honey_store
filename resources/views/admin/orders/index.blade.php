@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'إدارة الطلبات' : 'Orders Management')
@section('eyebrow', app()->isLocale('ar') ? 'تشغيل المبيعات' : 'Sales Operations')
@section('page-title', app()->isLocale('ar') ? 'الطلبات' : 'Orders')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    @php($deleteMessage = $isRtl ? 'حذف الطلب؟' : 'Delete this order?')
    <section class="admin-section">
        <form method="GET" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <label class="block xl:col-span-2">
                <span class="admin-form-label">{{ $isRtl ? 'بحث' : 'Search' }}</span>
                <input type="text" name="search" value="{{ request('search') }}" class="admin-form-input" placeholder="Name / email / phone">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'الحالة' : 'Status' }}</span>
                <select name="status" class="admin-form-select">
                    <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'من' : 'From' }}</span>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'إلى' : 'To' }}</span>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="admin-form-input">
            </label>
            <div class="admin-toolbar-actions xl:col-span-5">
                <button type="submit" class="admin-button-primary">{{ $isRtl ? 'تطبيق الفلاتر' : 'Apply Filters' }}</button>
                <a href="{{ route('admin.orders.index') }}" class="admin-button-secondary">{{ $isRtl ? 'إعادة ضبط' : 'Reset' }}</a>
            </div>
        </form>
    </section>

    <div class="admin-card-list mt-6">
        @forelse ($orders as $order)
            <article class="admin-record-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="admin-record-label">{{ $isRtl ? 'الطلب' : 'Order' }}</p>
                        <p class="admin-record-value-strong">#{{ $order->id }}</p>
                    </div>
                    <span class="admin-status-badge admin-status-badge--neutral">{{ $order->status }}</span>
                </div>

                <div class="admin-record-grid">
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'العميل' : 'Customer' }}</p>
                        <div class="admin-record-value">
                            <p class="font-semibold text-slate-950">{{ $order->customer_name }}</p>
                            <p>{{ $order->email }}</p>
                            <p>{{ $order->phone }}</p>
                        </div>
                    </div>
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'المنتج' : 'Product' }}</p>
                        <div class="admin-record-value">
                            <p class="font-semibold text-slate-950">{{ $order->product?->localized('name') ?? '—' }}</p>
                            @if ($panelRole === 'admin')
                                <p>{{ $order->product?->seller?->name ?? '—' }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'الكمية' : 'Qty' }}</p>
                        <p class="admin-record-value-strong">{{ $order->quantity }}</p>
                    </div>
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'الإجمالي' : 'Total' }}</p>
                        <p class="admin-record-value-strong">{{ $order->formattedTotal() }}</p>
                    </div>
                    <div class="admin-record-field sm:col-span-2">
                        <p class="admin-record-label">{{ $isRtl ? 'التاريخ' : 'Date' }}</p>
                        <p class="admin-record-value">{{ $order->created_at?->format('d M Y H:i') }}</p>
                    </div>
                    <div class="admin-record-field sm:col-span-2">
                        <p class="admin-record-label">{{ $isRtl ? 'تحديث الحالة' : 'Update Status' }}</p>
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="admin-form-select">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="admin-button-small">OK</button>
                        </form>
                    </div>
                </div>

                <div class="admin-record-actions">
                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-button-small">{{ $isRtl ? 'تفاصيل' : 'Details' }}</a>
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-button-danger">{{ $isRtl ? 'حذف' : 'Delete' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="admin-empty-state">{{ $isRtl ? 'لا توجد طلبات.' : 'No orders found.' }}</div>
        @endforelse
    </div>

    <section class="admin-glass mt-6 hidden overflow-hidden md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-slate-500 {{ $isRtl ? 'text-right' : '' }}">
                    <tr>
                        <th class="px-4 py-4">#</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'العميل' : 'Customer' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'المنتج' : 'Product' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الكمية' : 'Qty' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الإجمالي' : 'Total' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="admin-table-row align-top">
                            <td class="px-4 py-4 font-semibold text-slate-950">#{{ $order->id }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-950">{{ $order->customer_name }}</p>
                                <p class="mt-1 text-slate-500">{{ $order->email }}</p>
                                <p class="text-slate-500">{{ $order->phone }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-950">{{ $order->product?->localized('name') ?? '—' }}</p>
                                @if ($panelRole === 'admin')
                                    <p class="mt-1 text-slate-500">{{ $order->product?->seller?->name ?? '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $order->quantity }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $order->formattedTotal() }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-full border border-slate-200/80 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-700 shadow-[0_10px_22px_rgba(15,23,42,0.05)] outline-none transition focus:border-indigo-300">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="admin-button-small">OK</button>
                                </form>
                            </td>
                            <td class="px-4 py-4 text-slate-500">{{ $order->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-button-small">{{ $isRtl ? 'تفاصيل' : 'Details' }}</a>
                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-button-danger">{{ $isRtl ? 'حذف' : 'Delete' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">{{ $isRtl ? 'لا توجد طلبات.' : 'No orders found.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection