@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'إدارة البائعين' : 'Sellers Management')
@section('eyebrow', app()->isLocale('ar') ? 'البائعون' : 'Vendors')
@section('page-title', app()->isLocale('ar') ? 'البائعون' : 'Sellers')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    @php($deleteMessage = $isRtl ? 'حذف هذا البائع؟' : 'Delete this seller?')
    <div class="admin-section flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="admin-panel-kicker">{{ $isRtl ? 'إدارة البائعين' : 'Vendor Management' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'مساحة مخصصة لإدارة حسابات البائعين' : 'A dedicated space for managing seller accounts' }}</h3>
        </div>
        <a href="{{ route('admin.sellers.create') }}" class="admin-button-accent w-full sm:w-auto">{{ $isRtl ? 'إضافة بائع' : 'Add Seller' }}</a>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
        @forelse ($sellers as $seller)
            <article class="admin-glass p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $seller->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $seller->email }}</p>
                        <p class="text-sm text-slate-500">{{ $seller->phone }}</p>
                    </div>
                    <span class="admin-chip">{{ $seller->commission_rate }}%</span>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="admin-muted-card text-center">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $isRtl ? 'منتجات' : 'Products' }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ $seller->products_count }}</p>
                    </div>
                    <div class="admin-muted-card text-center">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $isRtl ? 'طلبات' : 'Orders' }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ $seller->orders_count }}</p>
                    </div>
                    <div class="admin-muted-card text-center">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $isRtl ? 'مبيعات' : 'Sales' }}</p>
                        <p class="mt-2 text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ number_format((float) $seller->completed_sales, 2) }}</p>
                    </div>
                </div>

                <div class="admin-toolbar-actions mt-6">
                    <a href="{{ route('admin.sellers.show', $seller) }}" class="admin-button-small">{{ $isRtl ? 'عرض' : 'View' }}</a>
                    <a href="{{ route('admin.sellers.edit', $seller) }}" class="admin-button-small">{{ $isRtl ? 'تعديل' : 'Edit' }}</a>
                    <form method="POST" action="{{ route('admin.sellers.destroy', $seller) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-button-danger">{{ $isRtl ? 'حذف' : 'Delete' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="admin-empty-state xl:col-span-2 2xl:col-span-3">{{ $isRtl ? 'لا يوجد بائعون بعد.' : 'No sellers created yet.' }}</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $sellers->links() }}</div>
@endsection