@extends('layouts.admin')

@section('title', app()->isLocale('ar') ? 'لوحة التحكم' : 'Dashboard')
@section('eyebrow', app()->isLocale('ar') ? 'ملخص الأعمال' : 'Business Snapshot')
@section('page-title', app()->isLocale('ar') ? 'الرئيسية' : 'Dashboard')

@section('content')
    @php($isRtl = app()->isLocale('ar'))
    @php($panelRole = $panelRole ?? 'admin')

    <section class="admin-glass relative overflow-hidden p-6 lg:p-8">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(99,102,241,0.18),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(211,168,99,0.16),transparent_34%)]"></div>
        <div class="relative grid gap-8 xl:grid-cols-[1.15fr_0.85fr]">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="admin-chip">{{ $isRtl ? 'لوحة مباشرة' : 'Live Overview' }}</span>
                    <span class="admin-chip">{{ $panelRole === 'seller' ? ($isRtl ? 'وصول البائع' : 'Seller Access') : ($isRtl ? 'وصول المدير' : 'Admin Access') }}</span>
                </div>
                <h3 class="mt-5 font-semibold text-slate-950 {{ $isRtl ? 'max-w-[13ch] text-[clamp(1.85rem,6.5vw,3.2rem)] leading-[1.08] tracking-[-0.025em]' : 'max-w-[11ch] text-[clamp(2.05rem,7vw,3.55rem)] leading-[0.98] tracking-[-0.05em]' }}">
                    {{ $isRtl ? 'لوحة تشغيل أكثر هدوءًا ووضوحًا.' : 'A calmer dashboard for daily store operations.' }}
                </h3>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 lg:text-lg">
                    {{ $isRtl ? 'تجربة مستوحاة من HeroUI: أسطح شفافة، تسلسل بصري أخف، وبطاقات ومكونات تجعل متابعة الطلبات والمبيعات أسرع.' : 'A HeroUI-inspired interface with layered glass surfaces, softer hierarchy, and cleaner cards to review sales, orders, and business signals faster.' }}
                </p>
                <div class="admin-toolbar-actions mt-7">
                    <a href="{{ route('admin.orders.index') }}" class="admin-button-primary">{{ $isRtl ? 'فتح الطلبات' : 'Open Orders' }}</a>
                    <a href="{{ route('admin.reports.index') }}" class="admin-button-secondary">{{ $isRtl ? 'عرض التقارير' : 'View Reports' }}</a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <article class="rounded-[1.75rem] border border-white/75 bg-white/75 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.10)] backdrop-blur-2xl sm:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $isRtl ? 'نبض التشغيل' : 'Operations Pulse' }}</p>
                            <h4 class="mt-2 text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'أين يحتاج الفريق الانتباه الآن' : 'Where the team should focus next' }}</h4>
                        </div>
                        <span class="admin-chip">{{ $isRtl ? 'مباشر' : 'Live' }}</span>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-[1.35rem] border border-slate-200/70 bg-slate-50/85 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $isRtl ? 'طلبات جديدة' : 'Pending' }}</p>
                            <p class="mt-3 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ number_format($stats['pending_orders']) }}</p>
                        </div>
                        <div class="rounded-[1.35rem] border border-slate-200/70 bg-slate-50/85 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $isRtl ? 'إجمالي المنتجات' : 'Catalog Size' }}</p>
                            <p class="mt-3 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ number_format($stats['products_count']) }}</p>
                        </div>
                        <div class="rounded-[1.35rem] border border-amber-200/70 bg-amber-50/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700/80">{{ $isRtl ? 'مبيعات مكتملة' : 'Completed Sales' }}</p>
                            <p class="mt-3 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ number_format($stats['total_sales'], 2) }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[1.75rem] border border-white/70 bg-white/70 p-5 shadow-[0_18px_40px_rgba(15,23,42,0.08)] backdrop-blur-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ $isRtl ? 'التجربة' : 'Experience' }}</p>
                    <h4 class="mt-3 text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'حركة أقل، وضوح أعلى' : 'Less noise, clearer signal' }}</h4>
                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $isRtl ? 'كل قسم أصبح أقرب لأسلوب بطاقات وواجهات HeroUI الحديثة مع حواف ناعمة وطبقات زجاجية.' : 'Each section now follows a softer HeroUI-style rhythm with rounded cards, translucent layers, and more deliberate spacing.' }}</p>
                </article>

                <article class="rounded-[1.75rem] border border-slate-900/80 bg-slate-950 p-5 text-white shadow-[0_24px_50px_rgba(15,23,42,0.28)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/50">{{ $isRtl ? 'الإجراء التالي' : 'Next Action' }}</p>
                    <h4 class="mt-3 text-lg font-semibold tracking-[-0.03em]">{{ $isRtl ? 'راجع الطلبات المفتوحة وحدث حالات الشحن.' : 'Review open orders and update shipping states.' }}</h4>
                    <a href="{{ route('admin.orders.index') }}" class="mt-5 inline-flex items-center rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15">
                        {{ $isRtl ? 'الانتقال للطلبات' : 'Go to Orders' }}
                    </a>
                </article>
            </div>
        </div>
    </section>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.partials.stat-card :label="$isRtl ? 'عدد الطلبات' : 'Orders Count'" :value="number_format($stats['orders_count'])" />
        <x-admin.partials.stat-card :label="$isRtl ? 'إجمالي المبيعات' : 'Total Sales'" :value="number_format($stats['total_sales'], 2)" tone="gold" />
        <x-admin.partials.stat-card :label="$isRtl ? 'عدد المنتجات' : 'Products Count'" :value="number_format($stats['products_count'])" />
        <x-admin.partials.stat-card :label="$isRtl ? 'طلبات جديدة' : 'Pending Orders'" :value="number_format($stats['pending_orders'])" tone="dark" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
        <section class="admin-glass p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-condensed text-sm uppercase tracking-[0.28em] text-indigo-500">{{ $isRtl ? 'أداء شهري' : 'Monthly Performance' }}</p>
                    <h3 class="mt-2 text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'المبيعات خلال آخر 12 شهرًا' : 'Sales over the last 12 months' }}</h3>
                </div>
                <div class="hidden flex-wrap gap-2 md:flex">
                    <span class="admin-chip">12M</span>
                    <span class="admin-chip">{{ $isRtl ? 'محدث' : 'Updated' }}</span>
                </div>
            </div>
            <div class="mt-6 h-[280px] sm:h-[320px] lg:h-[360px]">
                <canvas id="salesChart"
                        data-labels='@json($monthlySales['labels'], JSON_UNESCAPED_UNICODE)'
                        data-values='@json($monthlySales['values'], JSON_UNESCAPED_UNICODE)'
                        data-series-label="{{ $isRtl ? 'المبيعات' : 'Sales' }}"></canvas>
            </div>
        </section>

        <div class="space-y-6">
            <section class="admin-glass p-6">
                <p class="font-condensed text-sm uppercase tracking-[0.28em] text-indigo-500">{{ $isRtl ? 'مؤشرات سريعة' : 'Quick Notes' }}</p>
                <div class="mt-5 space-y-4">
                    <div class="rounded-[1.35rem] border border-slate-200/70 bg-white/70 p-4">
                        <p class="text-sm font-semibold text-slate-950">{{ $isRtl ? 'وصول حسب الدور' : 'Role-based access' }}</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $isRtl ? 'المدير يرى كل البيانات، بينما البائع يشاهد فقط المنتجات والطلبات المرتبطة به.' : 'Admins see the full marketplace, while sellers only view products and orders connected to their account.' }}</p>
                    </div>
                    <div class="rounded-[1.35rem] border border-slate-200/70 bg-white/70 p-4">
                        <p class="text-sm font-semibold text-slate-950">{{ $isRtl ? 'مزامنة الرصيد' : 'Balance sync' }}</p>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $isRtl ? 'تحديث الطلب إلى completed يعيد احتساب رصيد البائع تلقائيًا.' : 'Setting an order to completed automatically refreshes the seller balance.' }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-900/80 bg-slate-950 p-6 text-white shadow-[0_28px_70px_rgba(15,23,42,0.32)]">
                <p class="font-condensed text-sm uppercase tracking-[0.28em] text-honey-gold">{{ $isRtl ? 'تصدير سريع' : 'Export Ready' }}</p>
                <h3 class="mt-3 text-xl font-semibold tracking-[-0.03em]">{{ $isRtl ? 'التقارير والبيانات جاهزة للمشاركة.' : 'Reports and statement exports are ready to share.' }}</h3>
                <p class="mt-4 text-sm leading-7 text-white/70">{{ $isRtl ? 'يمكنك استخدام صفحة التقارير لتصفية المدة وتصدير CSV بسرعة دون مغادرة لوحة التشغيل.' : 'Use the reports page to filter ranges and export CSV statements without leaving the workspace.' }}</p>
                <a href="{{ route('admin.reports.index') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full border border-white/10 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/15 sm:w-auto">
                    {{ $isRtl ? 'فتح التقارير' : 'Open Reports' }}
                </a>
            </section>
        </div>
    </div>

    <section class="admin-glass mt-6 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-condensed text-sm uppercase tracking-[0.28em] text-indigo-500">{{ $isRtl ? 'آخر الطلبات' : 'Latest Orders' }}</p>
                <h3 class="mt-2 text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'ملخص النشاط الأخير' : 'Recent Activity' }}</h3>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="admin-button-secondary">{{ $isRtl ? 'عرض الكل' : 'View all' }}</a>
        </div>

        <div class="admin-card-list mt-6">
            @forelse ($recentOrders as $order)
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
                            <p class="admin-record-value-strong">{{ $order->customer_name }}</p>
                        </div>
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'المنتج' : 'Product' }}</p>
                            <p class="admin-record-value-strong">{{ $order->product?->localized('name') ?? '—' }}</p>
                        </div>
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'الإجمالي' : 'Total' }}</p>
                            <p class="admin-record-value-strong">{{ $order->formattedTotal() }}</p>
                        </div>
                        <div class="admin-record-field">
                            <p class="admin-record-label">{{ $isRtl ? 'التاريخ' : 'Date' }}</p>
                            <p class="admin-record-value">{{ $order->created_at?->format('d M Y') }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="admin-empty-state">{{ $isRtl ? 'لا توجد طلبات بعد.' : 'No orders yet.' }}</div>
            @endforelse
        </div>

        <div class="mt-6 hidden overflow-x-auto md:block">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200/70 text-left text-slate-500 {{ $isRtl ? 'text-right' : '' }}">
                        <th class="px-3 py-3">#</th>
                        <th class="px-3 py-3">{{ $isRtl ? 'العميل' : 'Customer' }}</th>
                        <th class="px-3 py-3">{{ $isRtl ? 'المنتج' : 'Product' }}</th>
                        <th class="px-3 py-3">{{ $isRtl ? 'الإجمالي' : 'Total' }}</th>
                        <th class="px-3 py-3">{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-3 py-3">{{ $isRtl ? 'التاريخ' : 'Date' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr class="admin-table-row last:border-b-0">
                            <td class="px-3 py-4 font-semibold text-slate-950">#{{ $order->id }}</td>
                            <td class="px-3 py-4 font-medium text-slate-950">{{ $order->customer_name }}</td>
                            <td class="px-3 py-4 text-slate-700">{{ $order->product?->localized('name') ?? '—' }}</td>
                            <td class="px-3 py-4 font-semibold text-slate-950">{{ $order->formattedTotal() }}</td>
                            <td class="px-3 py-4">
                                <span class="inline-flex rounded-full border border-slate-200/80 bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 shadow-[0_10px_24px_rgba(15,23,42,0.06)]">{{ $order->status }}</span>
                            </td>
                            <td class="px-3 py-4 text-slate-500">{{ $order->created_at?->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <script>
        window.addEventListener('load', () => {
            const salesChartContext = document.getElementById('salesChart');

            if (! salesChartContext || ! window.Chart) {
                return;
            }

            const chartLabels = JSON.parse(salesChartContext.dataset.labels || '[]');
            const chartValues = JSON.parse(salesChartContext.dataset.values || '[]');
            const seriesLabel = salesChartContext.dataset.seriesLabel || 'Sales';

            new window.Chart(salesChartContext, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: seriesLabel,
                        data: chartValues,
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99, 102, 241, 0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#D3A863',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                            },
                            ticks: {
                                color: '#64748B',
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: '#64748B',
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection