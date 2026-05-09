@php($isRtl = app()->isLocale('ar'))

<div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
    <div class="admin-section">
        <p class="admin-panel-kicker">{{ $isRtl ? 'بيانات البائع' : 'Seller Details' }}</p>
        <h3 class="admin-panel-title">{{ $isRtl ? 'إعدادات الحساب والعمولة' : 'Account and commission settings' }}</h3>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <label class="block md:col-span-2">
                <span class="admin-form-label">{{ $isRtl ? 'الاسم' : 'Name' }}</span>
                <input type="text" name="name" value="{{ old('name', $seller->name) }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">Email</span>
                <input type="email" name="email" value="{{ old('email', $seller->email) }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'الهاتف' : 'Phone' }}</span>
                <input type="text" name="phone" value="{{ old('phone', $seller->phone) }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'نسبة العمولة %' : 'Commission %' }}</span>
                <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="{{ old('commission_rate', $seller->commission_rate) }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'الرصيد' : 'Balance' }}</span>
                <input type="number" step="0.01" min="0" name="balance" value="{{ old('balance', $seller->balance) }}" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'كلمة المرور' : 'Password' }}</span>
                <input type="password" name="password" class="admin-form-input">
            </label>
            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'تأكيد كلمة المرور' : 'Confirm Password' }}</span>
                <input type="password" name="password_confirmation" class="admin-form-input">
            </label>
        </div>
    </div>

    <div class="admin-section">
        <p class="admin-panel-kicker">{{ $isRtl ? 'إجراءات' : 'Actions' }}</p>
        <h3 class="admin-panel-title">{{ $isRtl ? 'حفظ أو الرجوع للقائمة' : 'Save or return to the sellers list' }}</h3>
        <p class="mt-3 text-sm leading-7 text-slate-600">
            {{ $isRtl ? 'كل بائع يحصل على مساحة عمل خاصة داخل نفس لوحة التحكم، مع رؤية مقتصرة على منتجاته وطلباته وتقاريره.' : 'Each seller receives scoped access inside the same dashboard, limited to their own products, orders, and statements.' }}
        </p>
        <div class="mt-6 space-y-3">
            <button type="submit" class="admin-button-primary w-full">
                {{ $submitLabel }}
            </button>
            <a href="{{ route('admin.sellers.index') }}" class="admin-button-secondary flex w-full">
                {{ $isRtl ? 'رجوع للبائعين' : 'Back to sellers' }}
            </a>
        </div>
    </div>
</div>