@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'ملف البائع' : 'Seller Profile')
@section('eyebrow', app()->isLocale('ar') ? 'ملف البائع' : 'Seller Profile')
@section('page-title', app()->isLocale('ar') ? $seller->name : $seller->name)

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.partials.stat-card :label="$isRtl ? 'المبيعات الإجمالية' : 'Gross Sales'" :value="number_format($grossSales, 2)" tone="gold" />
        <x-admin.partials.stat-card :label="$isRtl ? 'صافي الأرباح' : 'Net Earnings'" :value="number_format($netEarnings, 2)" />
        <x-admin.partials.stat-card :label="$isRtl ? 'عدد الطلبات' : 'Orders Count'" :value="number_format($seller->orders()->count())" />
        <x-admin.partials.stat-card :label="$isRtl ? 'الرصيد الحالي' : 'Current Balance'" :value="number_format((float) $seller->balance, 2)" tone="dark" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'معلومات أساسية' : 'Seller Basics' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'بيانات الحساب والملف' : 'Account and profile details' }}</h3>
            <div class="mt-5 space-y-4 text-sm text-slate-600">
                <p><strong class="text-slate-950">Email:</strong> {{ $seller->email }}</p>
                <p><strong class="text-slate-950">{{ $isRtl ? 'الهاتف:' : 'Phone:' }}</strong> {{ $seller->phone }}</p>
                <p><strong class="text-slate-950">{{ $isRtl ? 'العمولة:' : 'Commission:' }}</strong> {{ $seller->commission_rate }}%</p>
                <p><strong class="text-slate-950">{{ $isRtl ? 'تاريخ الإنشاء:' : 'Created At:' }}</strong> {{ $seller->created_at?->format('d M Y') }}</p>
            </div>

            <h3 class="mt-8 text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'أحدث المنتجات' : 'Latest Products' }}</h3>
            <div class="mt-5 space-y-3">
                @forelse ($products as $product)
                    <div class="admin-muted-card">
                        <p class="font-semibold text-slate-950">{{ $product->localized('name') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $product->formattedPrice() }}</p>
                    </div>
                @empty
                    <p class="admin-muted-card text-sm text-slate-500">{{ $isRtl ? 'لا توجد منتجات مرتبطة.' : 'No linked products.' }}</p>
                @endforelse
            </div>
        </section>

        <section class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'آخر الطلبات' : 'Latest Orders' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'الطلبات الأخيرة المرتبطة بالبائع' : 'Recent seller-linked orders' }}</h3>
            <div class="admin-card-list mt-5">
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
                                <p class="admin-record-label">{{ $isRtl ? 'المنتج' : 'Product' }}</p>
                                <p class="admin-record-value-strong">{{ $order->product?->localized('name') ?? '—' }}</p>
                            </div>
                            <div class="admin-record-field">
                                <p class="admin-record-label">{{ $isRtl ? 'الإجمالي' : 'Total' }}</p>
                                <p class="admin-record-value-strong">{{ $order->formattedTotal() }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="admin-empty-state">{{ $isRtl ? 'لا توجد طلبات.' : 'No orders.' }}</div>
                @endforelse
            </div>

            <div class="mt-5 hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/70 text-left text-slate-500 {{ $isRtl ? 'text-right' : '' }}">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">{{ $isRtl ? 'المنتج' : 'Product' }}</th>
                            <th class="px-3 py-3">{{ $isRtl ? 'الإجمالي' : 'Total' }}</th>
                            <th class="px-3 py-3">{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr class="admin-table-row last:border-b-0">
                                <td class="px-3 py-4 font-semibold text-slate-950">#{{ $order->id }}</td>
                                <td class="px-3 py-4 text-slate-700">{{ $order->product?->localized('name') ?? '—' }}</td>
                                <td class="px-3 py-4 font-semibold text-slate-950">{{ $order->formattedTotal() }}</td>
                                <td class="px-3 py-4"><span class="admin-status-badge admin-status-badge--neutral">{{ $order->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $orders->links() }}</div>
        </section>
    </div>
@endsection