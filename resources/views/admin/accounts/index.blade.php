@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'الحسابات' : 'Accounts Ledger')
@section('eyebrow', app()->isLocale('ar') ? 'الإدارة المالية' : 'Finance Desk')
@section('page-title', app()->isLocale('ar') ? 'سجل الحسابات' : 'Accounts Ledger')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    @php($deleteMessage = $isRtl ? 'حذف هذا القيد؟' : 'Delete this entry?')

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <x-admin.partials.stat-card :label="$isRtl ? 'عدد القيود' : 'Entries Count'" :value="number_format($summary['entries_count'])" />
        <x-admin.partials.stat-card :label="$isRtl ? 'إجمالي القيم' : 'Total Amount'" :value="number_format($summary['total_amount'], 2)" tone="gold" />
        <x-admin.partials.stat-card :label="$isRtl ? 'مرفقات محفوظة' : 'Saved Attachments'" :value="number_format($summary['attachments_count'])" tone="dark" />
    </div>

    <section class="admin-section mt-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="admin-panel-kicker">{{ $isRtl ? 'إضافة قيد جديد' : 'Add New Entry' }}</p>
                <h3 class="admin-panel-title">{{ $isRtl ? 'نموذج تسجيل القيود المالية' : 'Financial record entry form' }}</h3>
            </div>
            <div class="admin-chip">
                {{ $isRtl ? 'الإدخال الجديد سيظهر مباشرة داخل سجل القيود بالأسفل.' : 'New entries are added directly to the records table below.' }}
            </div>
        </div>

        <form method="POST" action="{{ route('admin.accounts.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @csrf

            <label class="block xl:col-span-2">
                <span class="admin-form-label">{{ $isRtl ? 'العنوان' : 'Title' }}</span>
                <input type="text" name="title" value="{{ old('title') }}" class="admin-form-input" required>
            </label>

            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'اسم الشخص الذي دفع' : 'Payer Name' }}</span>
                <input type="text" name="payer_name" value="{{ old('payer_name') }}" class="admin-form-input" required>
            </label>

            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'القيمة' : 'Amount' }}</span>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="admin-form-input" required>
            </label>

            <label class="block">
                <span class="admin-form-label">{{ $isRtl ? 'التاريخ' : 'Date' }}</span>
                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" class="admin-form-input" required>
            </label>

            <label class="block xl:col-span-2">
                <span class="admin-form-label">{{ $isRtl ? 'الصور' : 'Images' }}</span>
                <input type="file" name="images[]" accept="image/*" multiple class="admin-form-file">
                <span class="mt-2 block text-xs text-slate-500">{{ $isRtl ? 'يمكنك اختيار أكثر من صورة دفعة واحدة.' : 'You can select multiple images at once.' }}</span>
            </label>

            <label class="block xl:col-span-2">
                <span class="admin-form-label">{{ $isRtl ? 'ملف مرفق' : 'Attachment File' }}</span>
                <input type="file" name="attachment" class="admin-form-file">
                <span class="mt-2 block text-xs text-slate-500">{{ $isRtl ? 'يمكنك رفع فاتورة أو ملف إثبات عند الحاجة.' : 'Upload an invoice or proof file when needed.' }}</span>
            </label>

            <label class="block md:col-span-2 xl:col-span-4">
                <span class="admin-form-label">{{ $isRtl ? 'الوصف' : 'Description' }}</span>
                <textarea name="description" rows="4" class="admin-form-textarea">{{ old('description') }}</textarea>
            </label>

            <div class="md:col-span-2 xl:col-span-4 flex justify-stretch sm:justify-end">
                <button type="submit" class="admin-button-primary w-full sm:w-auto">
                    {{ $isRtl ? 'حفظ القيد' : 'Save Entry' }}
                </button>
            </div>
        </form>
    </section>

    <section class="admin-section mt-6 md:hidden">
        <div>
            <p class="admin-panel-kicker">{{ $isRtl ? 'السجل' : 'Ledger' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'سجلات الحسابات المحفوظة' : 'Saved account records' }}</h3>
        </div>

        <div class="admin-card-list mt-6">
            @forelse ($entries as $entry)
                @php($imageUrls = $entry->imageUrls())
                <article class="admin-record-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="admin-record-label">{{ $isRtl ? 'القيد' : 'Entry' }}</p>
                            <p class="admin-record-value-strong">{{ $entry->title }}</p>
                        </div>
                        <span class="admin-chip">{{ $entry->formattedAmount() }}</span>
                    </div>

                    <div class="admin-record-grid">
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'الدافع' : 'Payer' }}</p>
                            <p class="admin-record-value-strong">{{ $entry->payer_name }}</p>
                        </div>
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'التاريخ' : 'Date' }}</p>
                            <p class="admin-record-value">{{ $entry->paid_at?->format('d M Y') }}</p>
                        </div>
                        <div class="admin-record-field sm:col-span-2">
                            <p class="admin-record-label">{{ $isRtl ? 'الوصف' : 'Description' }}</p>
                            <p class="admin-record-value">{{ $entry->description ?: ($isRtl ? 'لا يوجد وصف مضاف.' : 'No description added.') }}</p>
                        </div>
                        <div class="admin-record-field sm:col-span-2">
                            <p class="admin-record-label">{{ $isRtl ? 'المرفقات' : 'Attachments' }}</p>
                            <div class="admin-record-actions mt-3">
                                @foreach ($imageUrls as $index => $imageUrl)
                                    <a href="{{ $imageUrl }}" target="_blank" rel="noreferrer" class="admin-button-small">
                                        {{ $isRtl ? 'عرض الصورة' : 'View Image' }} {{ $index + 1 }}
                                    </a>
                                @endforeach

                                @if ($entry->attachmentUrl())
                                    <a href="{{ $entry->attachmentUrl() }}" target="_blank" rel="noreferrer" download="{{ $entry->attachmentLabel() }}" class="admin-button-small">
                                        {{ $isRtl ? 'تحميل الملف' : 'Download File' }}
                                    </a>
                                @endif

                                @if ($imageUrls === [] && ! $entry->attachmentUrl())
                                    <span class="admin-record-value">—</span>
                                @endif
                            </div>

                            @if ($entry->attachmentUrl())
                                <p class="admin-record-value mt-2 text-xs">{{ $entry->attachmentLabel() }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="admin-record-actions">
                        <form method="POST" action="{{ route('admin.accounts.destroy', $entry) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-button-danger">{{ $isRtl ? 'حذف' : 'Delete' }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="admin-empty-state">{{ $isRtl ? 'لا توجد قيود حسابات محفوظة بعد.' : 'No account entries saved yet.' }}</div>
            @endforelse
        </div>
    </section>

    <section class="admin-glass mt-6 hidden overflow-hidden md:block">
        <div class="border-b border-slate-200/70 px-6 py-5">
            <p class="admin-panel-kicker">{{ $isRtl ? 'السجل' : 'Ledger' }}</p>
            <h3 class="admin-panel-title">{{ $isRtl ? 'سجلات الحسابات المحفوظة' : 'Saved account records' }}</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50/80 text-left text-slate-500 {{ $isRtl ? 'text-right' : '' }}">
                    <tr>
                        <th class="px-4 py-4">#</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'العنوان' : 'Title' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الدافع' : 'Payer' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'القيمة' : 'Amount' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'المرفقات' : 'Attachments' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'الوصف' : 'Description' }}</th>
                        <th class="px-4 py-4">{{ $isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        @php($imageUrls = $entry->imageUrls())
                        <tr class="admin-table-row align-top">
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ ($entries->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-950">{{ $entry->title }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">{{ $isRtl ? 'تمت الإضافة' : 'Added' }} {{ $entry->created_at?->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $entry->payer_name }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $entry->paid_at?->format('d M Y') }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ $entry->formattedAmount() }}</td>
                            <td class="px-4 py-4">
                                <div class="flex min-w-[170px] flex-col gap-2">
                                    @foreach ($imageUrls as $index => $imageUrl)
                                        <a href="{{ $imageUrl }}" target="_blank" rel="noreferrer" class="admin-button-small">
                                            {{ $isRtl ? 'عرض الصورة' : 'View Image' }} {{ $index + 1 }}
                                        </a>
                                    @endforeach

                                    @if ($entry->attachmentUrl())
                                        <a href="{{ $entry->attachmentUrl() }}" target="_blank" rel="noreferrer" download="{{ $entry->attachmentLabel() }}" class="admin-button-small">
                                            {{ $isRtl ? 'تحميل الملف' : 'Download File' }}
                                        </a>
                                        <span class="text-xs text-slate-500">{{ $entry->attachmentLabel() }}</span>
                                    @endif

                                    @if ($imageUrls === [] && ! $entry->attachmentUrl())
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <div class="min-w-[260px] whitespace-normal leading-6">
                                    {{ $entry->description ?: ($isRtl ? 'لا يوجد وصف مضاف.' : 'No description added.') }}
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.accounts.destroy', $entry) }}" data-confirm="{{ $deleteMessage }}" onsubmit="return confirm(this.dataset.confirm);">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-button-danger">
                                        {{ $isRtl ? 'حذف' : 'Delete' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">{{ $isRtl ? 'لا توجد قيود حسابات محفوظة بعد.' : 'No account entries saved yet.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200/70 px-6 py-5">{{ $entries->links() }}</div>
    </section>
@endsection