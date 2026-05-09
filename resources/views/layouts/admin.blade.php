@php
    $isRtl = app()->isLocale('ar');
    $panelRole = $panelRole ?? 'admin';
    $account = auth('seller')->user() ?? auth('web')->user();
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => $isRtl ? 'الرئيسية' : 'Dashboard'],
        ['route' => 'admin.products.index', 'label' => $isRtl ? 'المنتجات' : 'Products'],
        ['route' => 'admin.orders.index', 'label' => $isRtl ? 'الطلبات' : 'Orders'],
        ['route' => 'admin.reports.index', 'label' => $isRtl ? 'التقارير' : 'Reports'],
    ];

    if ($panelRole === 'admin') {
        $navItems[] = ['route' => 'admin.accounts.index', 'label' => $isRtl ? 'الحسابات' : 'Accounts'];
        $navItems[] = ['route' => 'admin.sellers.index', 'label' => $isRtl ? 'البائعون' : 'Sellers'];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $isRtl ? 'لوحة التحكم' : 'Admin Dashboard')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Manrope', 'Cairo', sans-serif;
        }

        .font-condensed {
            font-family: 'Space Grotesk', 'Manrope', sans-serif !important;
        }

        html[dir="rtl"] body {
            font-family: 'Cairo', 'Manrope', sans-serif;
        }

        html[dir="rtl"] .font-condensed {
            font-family: 'Cairo', 'Manrope', sans-serif !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-[#eef2ff] text-slate-950 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-24 top-[-4rem] h-[26rem] w-[26rem] rounded-full bg-[radial-gradient(circle,rgba(99,102,241,0.22),transparent_60%)]"></div>
        <div class="absolute right-[-6rem] top-20 h-[32rem] w-[32rem] rounded-full bg-[radial-gradient(circle,rgba(211,168,99,0.2),transparent_60%)]"></div>
        <div class="absolute bottom-[-12rem] left-1/3 h-[30rem] w-[30rem] rounded-full bg-[radial-gradient(circle,rgba(56,189,248,0.16),transparent_62%)]"></div>
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.62),rgba(244,247,251,0.94))]"></div>
    </div>

    <div class="relative mx-auto max-w-[1600px] p-4 lg:p-6">
        <div class="grid min-h-[calc(100vh-2rem)] gap-6 lg:grid-cols-[290px_minmax(0,1fr)]">
            <aside class="admin-glass-dark relative hidden overflow-hidden p-6 text-white lg:sticky lg:top-6 lg:block lg:h-[calc(100vh-3rem)] lg:self-start">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(99,102,241,0.28),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(211,168,99,0.22),transparent_30%)]"></div>
                <div class="admin-sidebar-scroll relative flex h-full min-h-0 flex-col">
                    <div class="rounded-[1.6rem] border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-sm font-bold text-white shadow-[0_14px_28px_rgba(15,23,42,0.2)]">HS</span>
                            <div>
                                <p class="font-condensed text-xs uppercase tracking-[0.32em] text-white/50">Honey Store</p>
                                <h1 class="mt-1 text-xl font-semibold tracking-[-0.03em]">{{ $isRtl ? 'لوحة الإدارة' : 'Control Center' }}</h1>
                            </div>
                        </div>

                    </div>

                    <nav class="mt-8 space-y-2.5">
                        @foreach ($navItems as $item)
                            @php($active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                            <a href="{{ route($item['route']) }}"
                               class="group flex items-center justify-between rounded-[1.35rem] border px-4 py-3.5 text-sm font-semibold transition duration-200 {{ $active ? 'border-white/15 bg-white/10 text-white shadow-[0_18px_32px_rgba(15,23,42,0.22)]' : 'border-transparent bg-transparent text-white/70 hover:border-white/10 hover:bg-white/10 hover:text-white' }}">
                                <span class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border text-[11px] font-bold uppercase tracking-[0.16em] {{ $active ? 'border-white/15 bg-white/10 text-white' : 'border-white/10 bg-white/5 text-white/50 group-hover:border-white/15 group-hover:text-white/75' }}">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <span class="h-2.5 w-2.5 rounded-full {{ $active ? 'bg-honey-gold shadow-[0_0_0_6px_rgba(211,168,99,0.14)]' : 'bg-white/15 group-hover:bg-white/35' }}"></span>
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-auto rounded-[1.8rem] border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.28em] text-white/45">{{ $isRtl ? 'الحساب الحالي' : 'Current Session' }}</p>
                                <p class="mt-2 text-lg font-semibold tracking-[-0.02em]">{{ $account->name ?? 'Guest' }}</p>
                                <p class="mt-1 text-sm text-white/60">{{ $panelRole === 'seller' ? 'Seller' : 'Admin' }}</p>
                            </div>
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-sm font-bold text-white">{{ strtoupper(substr($account->name ?? 'G', 0, 1)) }}</span>
                        </div>

                        <form method="POST" action="{{ route('admin.logout') }}" class="mt-5">
                            @csrf
                            <button type="submit" class="w-full rounded-full border border-white/10 bg-white px-4 py-3 text-sm font-semibold text-slate-950 shadow-[0_16px_28px_rgba(255,255,255,0.14)] transition duration-200 hover:-translate-y-0.5 hover:bg-slate-100">
                                {{ $isRtl ? 'تسجيل الخروج' : 'Logout' }}
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-screen flex-col gap-6 lg:min-h-[calc(100vh-3rem)]">
                <section class="admin-glass px-4 py-4 lg:hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-sm font-bold text-white shadow-[0_14px_28px_rgba(15,23,42,0.16)]">HS</span>
                                <div class="min-w-0">
                                    <p class="font-condensed text-[11px] uppercase tracking-[0.24em] text-slate-500">Honey Store</p>
                                    <p class="mt-1 truncate text-lg font-semibold tracking-[-0.03em] text-slate-950">{{ $isRtl ? 'لوحة الإدارة' : 'Control Center' }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">{{ $account->name ?? 'Guest' }} · {{ $panelRole === 'seller' ? 'Seller' : 'Admin' }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.logout') }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-full border border-slate-200/80 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-[0_12px_24px_rgba(15,23,42,0.08)] transition duration-200 hover:bg-white hover:text-slate-950">
                                {{ $isRtl ? 'خروج' : 'Logout' }}
                            </button>
                        </form>
                    </div>

                    <nav class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1">
                        @foreach ($navItems as $item)
                            @php($active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                            <a href="{{ route($item['route']) }}"
                               class="shrink-0 rounded-full border px-4 py-2.5 text-sm font-semibold transition duration-200 {{ $active ? 'border-slate-900/10 bg-slate-950 text-white shadow-[0_16px_34px_rgba(15,23,42,0.22)]' : 'border-slate-200/80 bg-white/80 text-slate-700 shadow-[0_10px_22px_rgba(15,23,42,0.06)] hover:bg-white hover:text-slate-950' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </section>

                <header class="admin-glass px-4 py-4 sm:px-5 sm:py-5 lg:px-7">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="admin-chip">@yield('eyebrow', $isRtl ? 'لوحة التحكم' : 'Admin Panel')</span>
                                <span class="admin-chip">{{ $panelRole === 'seller' ? ($isRtl ? 'وضع البائع' : 'Seller Mode') : ($isRtl ? 'وضع المدير' : 'Admin Mode') }}</span>
                            </div>
                            <h2 class="mt-4 text-2xl font-semibold tracking-[-0.05em] text-slate-950 sm:text-3xl lg:text-[2.35rem]">@yield('page-title', $isRtl ? 'نظرة عامة' : 'Overview')</h2>
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">{{ $isRtl ? 'تصميم أكثر هدوءًا وحداثة، مع مكونات زجاجية وطبقات واضحة للمعلومات والإجراءات.' : 'A calmer, more modern workspace with glass surfaces, soft hierarchy, and clearer action points.' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="admin-chip">{{ now()->format('d M Y') }}</span>
                            <a href="{{ route('home') }}" class="admin-button-secondary">{{ $isRtl ? 'عرض الموقع' : 'View Storefront' }}</a>
                        </div>
                    </div>
                </header>

                <main class="flex-1 space-y-6">
                    @if (session('status'))
                        <div class="rounded-[1.75rem] border border-emerald-200/70 bg-emerald-50/85 px-5 py-4 text-sm text-emerald-800 shadow-[0_18px_40px_rgba(16,185,129,0.12)] backdrop-blur">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-[1.75rem] border border-red-200/70 bg-red-50/85 px-5 py-4 text-sm text-red-800 shadow-[0_18px_40px_rgba(239,68,68,0.08)] backdrop-blur">
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    </div>
</body>
</html>
