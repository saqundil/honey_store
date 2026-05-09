@php
    $isRtl = app()->isLocale('ar');
    $translations = $product->translations ?? [];
@endphp

<div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <div class="space-y-6">
        <div class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'البيانات الأساسية' : 'Core Product Data' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'معلومات المنتج الرئيسية' : 'Primary product information' }}</h3>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="admin-form-label">Slug</span>
                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="admin-form-input">
                </label>
                <label class="block">
                    <span class="admin-form-label">SKU</span>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="admin-form-input">
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'السعر' : 'Price' }}</span>
                    <input type="number" step="0.01" min="0" name="price_value" value="{{ old('price_value', $product->price_value) }}" class="admin-form-input">
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'العملة' : 'Currency' }}</span>
                    <input type="text" name="currency" value="{{ old('currency', $product->currency) }}" class="admin-form-input">
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'مكان العملة' : 'Currency Position' }}</span>
                    <select name="currency_position" class="admin-form-select">
                        <option value="prefix" @selected(old('currency_position', $product->currency_position) === 'prefix')>{{ $isRtl ? 'قبل الرقم' : 'Before amount' }}</option>
                        <option value="suffix" @selected(old('currency_position', $product->currency_position) === 'suffix')>{{ $isRtl ? 'بعد الرقم' : 'After amount' }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'الكسور العشرية' : 'Price Decimals' }}</span>
                    <input type="number" min="0" max="4" name="price_decimals" value="{{ old('price_decimals', $product->price_decimals ?? 2) }}" class="admin-form-input">
                </label>
                <label class="block">
                    <span class="admin-form-label">{{ $isRtl ? 'الترتيب' : 'Sort Order' }}</span>
                    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="admin-form-input">
                </label>
                @if ($panelRole === 'admin')
                    <label class="block md:col-span-2">
                        <span class="admin-form-label">{{ $isRtl ? 'البائع' : 'Seller' }}</span>
                        <select name="seller_id" class="admin-form-select">
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected((string) old('seller_id', $product->seller_id) === (string) $seller->id)>{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </div>

        <div class="admin-section">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="admin-panel-kicker">{{ $isRtl ? 'المحتوى المترجم' : 'Translated Content' }}</p>
                    <h3 class="admin-panel-title">{{ $isRtl ? 'نسختا اللغة للمنتج' : 'Localized content blocks' }}</h3>
                </div>
                <label class="inline-flex items-center gap-3 rounded-full border border-slate-200/80 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_12px_24px_rgba(15,23,42,0.06)]">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-black/10 text-honey-gold focus:ring-honey-gold">
                    <span>{{ $isRtl ? 'منتج نشط' : 'Active Product' }}</span>
                </label>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="admin-soft-card">
                    <h4 class="text-lg font-semibold tracking-[-0.03em] text-slate-950">English</h4>
                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="admin-form-label">Name</span>
                            <input type="text" name="en_name" value="{{ old('en_name', data_get($translations, 'en.name')) }}" class="admin-form-input">
                        </label>
                        <label class="block">
                            <span class="admin-form-label">Excerpt</span>
                            <textarea name="en_excerpt" rows="3" class="admin-form-textarea">{{ old('en_excerpt', data_get($translations, 'en.excerpt')) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="admin-form-label">Description</span>
                            <textarea name="en_description" rows="6" class="admin-form-textarea">{{ old('en_description', data_get($translations, 'en.description')) }}</textarea>
                        </label>
                    </div>
                </div>
                <div class="admin-soft-card">
                    <h4 class="text-lg font-semibold tracking-[-0.03em] text-slate-950">العربية</h4>
                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="admin-form-label">الاسم</span>
                            <input type="text" name="ar_name" value="{{ old('ar_name', data_get($translations, 'ar.name')) }}" class="admin-form-input">
                        </label>
                        <label class="block">
                            <span class="admin-form-label">الوصف المختصر</span>
                            <textarea name="ar_excerpt" rows="3" class="admin-form-textarea">{{ old('ar_excerpt', data_get($translations, 'ar.excerpt')) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="admin-form-label">الوصف الكامل</span>
                            <textarea name="ar_description" rows="6" class="admin-form-textarea">{{ old('ar_description', data_get($translations, 'ar.description')) }}</textarea>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'الصورة' : 'Product Image' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'صورة المعاينة' : 'Preview image' }}</h3>
            <div class="mt-5 overflow-hidden rounded-[1.5rem] border border-dashed border-slate-200/80 bg-slate-50/80">
                <img src="{{ $product->imageUrl() }}" alt="Product image" class="h-56 w-full object-cover sm:h-72">
            </div>
            <label class="mt-5 block">
                <span class="admin-form-label">{{ $isRtl ? 'رفع صورة جديدة' : 'Upload New Image' }}</span>
                <input type="file" name="image" accept="image/*" class="admin-form-file">
            </label>
        </div>

        <div class="admin-section">
            <p class="admin-panel-kicker">{{ $isRtl ? 'إجراءات' : 'Actions' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'احفظ أو ارجع للقائمة' : 'Save or return to the listing' }}</h3>
            <div class="mt-5 space-y-3">
                <button type="submit" class="admin-button-primary w-full">
                    {{ $submitLabel }}
                </button>
                <a href="{{ route('admin.products.index') }}" class="admin-button-secondary flex w-full">
                    {{ $isRtl ? 'رجوع للقائمة' : 'Back to list' }}
                </a>
            </div>
        </div>
    </div>
</div>