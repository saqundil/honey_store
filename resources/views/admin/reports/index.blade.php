@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'التقارير وكشف الحساب' : 'Reports & Statements')
@section('eyebrow', app()->isLocale('ar') ? 'التقارير' : 'Reports')
@section('page-title', app()->isLocale('ar') ? 'كشف الحساب' : 'Statements')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    <section class="admin-section">
        <form method="GET" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @if ($panelRole === 'admin')
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'البائع' : 'Seller' }}</span>
                    <select name="seller_id" class="admin-form-select">
                        <option value="">{{ $isRtl ? 'كل البائعين' : 'All sellers' }}</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected((string) ($filters['seller_id'] ?? '') === (string) $seller->id)>{{ $seller->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'من' : 'From' }}</span>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'إلى' : 'To' }}</span>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="admin-form-input">
            </label>
            <div class="admin-toolbar-actions sm:items-end md:col-span-2 xl:col-span-2">
                <button type="submit" class="admin-button-primary">{{ $isRtl ? 'تطبيق' : 'Apply' }}</button>
                <a href="{{ route('admin.reports.index') }}" class="admin-button-secondary">{{ $isRtl ? 'إعادة ضبط' : 'Reset' }}</a>
                <button type="submit" name="export" value="csv" class="admin-button-accent">CSV</button>
            </div>
        </form>
    </section>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.partials.stat-card :label="$isRtl ? 'عدد الطلبات' : 'Orders Count'" :value="number_format($summary['orders_count'])" />
        <x-admin.partials.stat-card :label="$isRtl ? 'إجمالي المبيعات' : 'Gross Sales'" :value="number_format($summary['gross_sales'], 2)" tone="gold" />
        <x-admin.partials.stat-card :label="$isRtl ? 'إجمالي العمولة' : 'Commission Total'" :value="number_format($summary['commission_total'], 2)" />
        <x-admin.partials.stat-card :label="$isRtl ? 'صافي الأرباح' : 'Net Earnings'" :value="number_format($summary['net_earnings'], 2)" tone="dark" />
    </div>

    <div class="admin-card-list mt-6">
        @forelse ($orders as $order)
            <article class="admin-record-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="admin-record-label">{{ $isRtl ? 'البيان' : 'Statement' }}</p>
                        <p class="admin-record-value-strong">#{{ $order->id }}</p>
                    </div>
                    <span class="admin-status-badge admin-status-badge--neutral">{{ $order->status }}</span>
                </div>

                <div class="admin-record-grid">
                    @if ($panelRole === 'admin')
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'البائع' : 'Seller' }}</p>
                            <p class="admin-record-value-strong">{{ $order->product?->seller?->name ?? '—' }}</p>
                        </div>
                    @endif
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'المنتج' : 'Product' }}</p>
                        <p class="admin-record-value-strong">{{ $order->product?->localized('name') ?? '—' }}</p>
                    </div>
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'التاريخ' : 'Date' }}</p>
                        <p class="admin-record-value">{{ $order->created_at?->format('d M Y') }}</p>
                    </div>
                    <div class="admin-record-field">
                        <p class="admin-record-label">{{ $isRtl ? 'المبلغ' : 'Amount' }}</p>
                        <p class="admin-record-value-strong">{{ $order->formattedTotal() }}</p>
                    </div>
                </div>
            </article>
        @empty
            <div class="admin-empty-state">{{ $isRtl ? 'لا توجد بيانات لهذا النطاق.' : 'No statement data for the selected range.' }}</div>
        @endforelse
    </div>

    <section class="admin-glass mt-6 hidden overflow-hidden md:block">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-slate-500 {{ $isRtl ? 'text-right' : '' }}">
                    <tr>
                        <th class="px-4 py-4">#</th>
                        @if ($panelRole === 'admin')
                            <th class="px-4 py-4">{{ $isRtl ? 'البائع' : 'Seller' }}</th>
                        @endif
                        <th class="px-4 py-4">{{ $isRtl ? 'المنتج' : 'Product' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'المبلغ' : 'Amount' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="admin-table-row">
                            <td class="px-4 py-4 font-semibold text-slate-950">#{{ $order->id }}</td>
                            @if ($panelRole === 'admin')
                                <td class="px-4 py-4 text-slate-700">{{ $order->product?->seller?->name ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-4 text-slate-700">{{ $order->product?->localized('name') ?? '—' }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $order->created_at?->format('d M Y') }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $order->formattedTotal() }}</td>
                            <td class="px-4 py-4"><span class="admin-status-badge admin-status-badge--neutral">{{ $order->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $panelRole === 'admin' ? 6 : 5 }}" class="px-4 py-10 text-center text-slate-500">{{ $isRtl ? 'لا توجد بيانات لهذا النطاق.' : 'No statement data for the selected range.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection