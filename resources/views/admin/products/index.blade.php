@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'إدارة المنتجات' : 'Products Management')
@section('eyebrow', app()->isLocale('ar') ? 'إدارة الكتالوج' : 'Catalog Management')
@section('page-title', app()->isLocale('ar') ? 'المنتجات' : 'Products')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    @php($deleteMessage = $isRtl ? 'هل أنت متأكد من حذف المنتج؟' : 'Delete this product?')
    <div class="admin-section">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <form method="GET" class="grid flex-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'بحث' : 'Search' }}</span>
                    <input type="text" name="search" value="{{ request('search') }}" class="admin-form-input" placeholder="SKU / slug / name">
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'الحالة' : 'Status' }}</span>
                    <select name="status" class="admin-form-select">
                        <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ $isRtl ? 'نشط' : 'Active' }}</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>{{ $isRtl ? 'معطل' : 'Inactive' }}</option>
                    </select>
                </label>
                @if ($panelRole === 'admin')
                    <label class="block">
                        <span class="admin-form-label">{{ $isRtl ? 'البائع' : 'Seller' }}</span>
                        <select name="seller_id" class="admin-form-select">
                            <option value="">{{ $isRtl ? 'الكل' : 'All Sellers' }}</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected((string) request('seller_id') === (string) $seller->id)>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <div class="admin-toolbar-actions items-stretch sm:items-end">
                    <button type="submit" class="admin-button-primary">{{ $isRtl ? 'تصفية' : 'Filter' }}</button>
                    <a href="{{ route('admin.products.index') }}" class="admin-button-secondary">{{ $isRtl ? 'إعادة ضبط' : 'Reset' }}</a>
                </div>
            </form>

            <a href="{{ route('admin.products.create') }}" class="admin-button-accent w-full sm:w-auto">
                {{ $isRtl ? 'إضافة منتج' : 'Add Product' }}
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
        @forelse ($products as $product)
            <article class="admin-glass overflow-hidden">
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" class="h-56 w-full object-cover">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $product->localized('name') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $product->sku }} • {{ $product->slug }}</p>
                        </div>
                        <span class="admin-status-badge {{ $product->is_active ? 'admin-status-badge--success' : 'admin-status-badge--danger' }}">
                            {{ $product->is_active ? ($isRtl ? 'نشط' : 'Active') : ($isRtl ? 'معطل' : 'Inactive') }}
                        </span>
                    </div>

                    <p class="mt-4 line-clamp-3 text-sm leading-7 text-slate-600">{{ $product->localized('excerpt') }}</p>

                    <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                        <span class="admin-chip">{{ $product->formattedPrice() }}</span>
                        <span class="admin-chip">{{ $isRtl ? 'ترتيب:' : 'Sort:' }} {{ $product->sort_order }}</span>
                        @if ($panelRole === 'admin')
                            <span class="admin-chip">{{ $product->seller?->name ?? ($isRtl ? 'غير معين' : 'Unassigned') }}</span>
                        @endif
                    </div>

                    <div class="admin-toolbar-actions mt-6">
                        <a href="{{ route('admin.products.edit', $product) }}" class="admin-button-small">{{ $isRtl ? 'تعديل' : 'Edit' }}</a>
                        <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="admin-button-small">{{ $product->is_active ? ($isRtl ? 'تعطيل' : 'Disable') : ($isRtl ? 'تفعيل' : 'Enable') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-button-danger">{{ $isRtl ? 'حذف' : 'Delete' }}</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="admin-empty-state xl:col-span-2 2xl:col-span-3">
                {{ $isRtl ? 'لا توجد منتجات مطابقة.' : 'No products found.' }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection