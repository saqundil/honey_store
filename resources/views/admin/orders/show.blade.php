@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'تفاصيل الطلب' : 'Order Details')
@section('eyebrow', app()->isLocale('ar') ? 'تفاصيل الطلب' : 'Order Detail')
@section('page-title', app()->isLocale('ar') ? 'الطلب #'.$order->id : 'Order #'.$order->id)

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <section class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'بيانات العميل' : 'Customer Information' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'ملف الطلب والعميل' : 'Customer and order snapshot' }}</h3>
            <div class="mt-5 grid gap-4 md:grid-cols-2 text-sm">
                <div class="admin-muted-card"><span class="block text-slate-500">{{ $isRtl ? 'الاسم' : 'Name' }}</span><strong class="mt-2 block text-slate-950">{{ $order->customer_name }}</strong></div>
                <div class="admin-muted-card"><span class="block text-slate-500">Email</span><strong class="mt-2 block text-slate-950">{{ $order->email }}</strong></div>
                <div class="admin-muted-card"><span class="block text-slate-500">{{ $isRtl ? 'الهاتف' : 'Phone' }}</span><strong class="mt-2 block text-slate-950">{{ $order->phone }}</strong></div>
                <div class="admin-muted-card"><span class="block text-slate-500">{{ $isRtl ? 'الحالة' : 'Status' }}</span><strong class="mt-2 block text-slate-950">{{ $order->status }}</strong></div>
            </div>

            <h3 class="mt-8 text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'تفاصيل المنتج' : 'Product Snapshot' }}</h3>
            <div class="mt-5 rounded-[1.5rem] border border-slate-200/70 bg-white/70 p-5 shadow-[0_16px_34px_rgba(15,23,42,0.06)]">
                <div class="flex flex-col gap-5 md:flex-row">
                    <img src="{{ $order->product?->imageUrl() }}" alt="{{ $order->product?->localized('name') }}" class="h-44 w-full max-w-[220px] rounded-[1.5rem] object-cover">
                    <div class="space-y-3 text-sm">
                        <p class="text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $order->product?->localized('name') ?? '—' }}</p>
                        <p class="text-slate-500">SKU: {{ $order->product?->sku }}</p>
                        @if ($panelRole === 'admin')
                            <p class="text-slate-500">{{ $isRtl ? 'البائع:' : 'Seller:' }} {{ $order->product?->seller?->name ?? '—' }}</p>
                        @endif
                        <p class="text-slate-500">{{ $isRtl ? 'الكمية:' : 'Quantity:' }} {{ $order->quantity }}</p>
                        <p class="text-slate-500">{{ $isRtl ? 'سعر الوحدة:' : 'Unit price:' }} {{ $order->formattedAmount($order->unit_price) }}</p>
                        <p class="font-semibold text-slate-950">{{ $isRtl ? 'الإجمالي:' : 'Total:' }} {{ $order->formattedTotal() }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-6">
            <div class="admin-section">
                <p class="admin-panel-kicker">{{ $isRtl ? 'تحديث الحالة' : 'Update Status' }}</p>
                <h3 class="admin-panel-title">{{ $isRtl ? 'إجراء سريع على الطلب' : 'Quick order action' }}</h3>
                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="admin-form-select">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="admin-button-primary w-full">{{ $isRtl ? 'حفظ الحالة' : 'Save Status' }}</button>
                </form>
            </div>

            <div class="admin-section">
                <p class="admin-panel-kicker">{{ $isRtl ? 'ملاحظات العميل' : 'Customer Notes' }}</p>
                <h3 class="admin-panel-title">{{ $isRtl ? 'تعليقات إضافية على الطلب' : 'Additional request notes' }}</h3>
                <p class="mt-4 rounded-[1.35rem] border border-slate-200/70 bg-slate-50/80 p-4 text-sm leading-7 text-slate-600">{{ $order->notes ?: ($isRtl ? 'لا توجد ملاحظات.' : 'No notes provided.') }}</p>
            </div>
        </section>
    </div>
@endsection