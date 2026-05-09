@php($isRtl = app()->isLocale('ar'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isRtl ? 'تسجيل دخول الإدارة' : 'Admin Login' }}</title>
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

        html[dir="rtl"] body,
        html[dir="rtl"] .font-condensed {
            font-family: 'Cairo', 'Manrope', sans-serif !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-950 text-white">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-20 top-[-4rem] h-[26rem] w-[26rem] rounded-full bg-[radial-gradient(circle,rgba(99,102,241,0.26),transparent_58%)]"></div>
        <div class="absolute right-[-8rem] top-16 h-[30rem] w-[30rem] rounded-full bg-[radial-gradient(circle,rgba(211,168,99,0.18),transparent_58%)]"></div>
        <div class="absolute bottom-[-10rem] left-1/3 h-[28rem] w-[28rem] rounded-full bg-[radial-gradient(circle,rgba(56,189,248,0.14),transparent_60%)]"></div>
    </div>

    <div class="relative grid min-h-screen gap-4 p-3 sm:gap-6 sm:p-4 lg:grid-cols-[1.05fr_0.95fr] lg:p-6">
        <section class="admin-glass-dark hidden overflow-hidden lg:flex lg:flex-col lg:justify-between lg:p-10">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="admin-chip">Honey Store</span>
                    <span class="admin-chip">{{ $isRtl ? 'وصول آمن' : 'Secure Access' }}</span>
                </div>
                <h1 class="mt-8 max-w-[11ch] text-[clamp(2.6rem,5.4vw,4.4rem)] font-semibold leading-[0.98] tracking-[-0.06em] text-white">
                    {{ $isRtl ? 'لوحة تشغيل عصرية لإدارة كامل المتجر.' : 'A modern control surface for the entire store.' }}
                </h1>
                <p class="mt-6 max-w-xl text-base leading-8 text-white/70">
                    {{ $isRtl ? 'نفس لغة التصميم الحديثة في كامل الإدارة: وضوح أعلى، تنقل أسرع، ومكونات خفيفة تشبه HeroUI.' : 'The whole admin is now aligned around a cleaner, lighter HeroUI-inspired system with softer depth and clearer actions.' }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-[1.6rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                    <p class="text-[11px] uppercase tracking-[0.22em] text-white/45">{{ $isRtl ? 'الإدارة' : 'Admin' }}</p>
                    <p class="mt-3 text-lg font-semibold">admin@honey-store.test</p>
                    <p class="mt-1 text-sm text-white/60">password</p>
                </div>
                <div class="rounded-[1.6rem] border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                    <p class="text-[11px] uppercase tracking-[0.22em] text-white/45">{{ $isRtl ? 'البائع' : 'Seller' }}</p>
                    <p class="mt-3 text-lg font-semibold">seller@honey-store.test</p>
                    <p class="mt-1 text-sm text-white/60">password</p>
                </div>
            </div>
        </section>

        <section class="flex items-start justify-center px-1 py-4 sm:px-2 sm:py-6 lg:items-center lg:px-8">
            <div class="admin-glass w-full max-w-xl p-6 text-slate-950 sm:p-8 lg:p-10">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="admin-chip">{{ $isRtl ? 'تسجيل الدخول' : 'Sign In' }}</span>
                    <span class="admin-chip">{{ $isRtl ? 'مدير أو بائع' : 'Admin or Seller' }}</span>
                </div>
                <h2 class="mt-5 text-3xl font-semibold tracking-[-0.05em] text-slate-950 lg:text-[2.4rem]">{{ $isRtl ? 'الدخول إلى مساحة الإدارة' : 'Access the admin workspace' }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $isRtl ? 'اختر نوع الحساب وأدخل بياناتك للانتقال إلى اللوحة المناسبة.' : 'Choose the account type and use your credentials to enter the correct workspace.' }}</p>

                <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label class="admin-form-label">{{ $isRtl ? 'نوع الحساب' : 'Account Type' }}</label>
                        <select name="account_type" class="admin-form-select">
                            <option value="admin" @selected(old('account_type', 'admin') === 'admin')>Admin</option>
                            <option value="seller" @selected(old('account_type') === 'seller')>Seller</option>
                        </select>
                    </div>

                    <div>
                        <label class="admin-form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="admin-form-input" required>
                    </div>

                    <div>
                        <label class="admin-form-label">{{ $isRtl ? 'كلمة المرور' : 'Password' }}</label>
                        <input type="password" name="password" class="admin-form-input" required>
                    </div>

                    <label class="flex items-center gap-3 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 bg-white text-indigo-500 focus:ring-indigo-300">
                        <span>{{ $isRtl ? 'تذكرني' : 'Remember me' }}</span>
                    </label>

                    <button type="submit" class="admin-button-primary w-full">
                        {{ $isRtl ? 'دخول' : 'Login' }}
                    </button>
                </form>

                <div class="mt-8 rounded-[1.5rem] border border-slate-200/70 bg-slate-50/80 px-5 py-4 text-sm text-slate-600 shadow-[0_16px_32px_rgba(15,23,42,0.06)]">
                    <p>Admin: admin@honey-store.test / password</p>
                    <p class="mt-1">Seller: seller@honey-store.test / password</p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>