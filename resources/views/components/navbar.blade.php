{{-- resources/views/components/navbar.blade.php --}}
@php
    $currentLocale = app()->currentLocale();
    $currentRoute = Route::currentRouteName();
    $desktopNavItem = fn (string ...$routes) => collect($routes)->contains($currentRoute)
        ? 'bg-[#2c1b0b] text-white shadow-[0_14px_28px_rgba(44,27,11,0.14)]'
        : 'text-honey-nav hover:bg-[#2c1b0b]/5 hover:text-honey-orange';
    $mobileNavItem = fn (string ...$routes) => collect($routes)->contains($currentRoute)
        ? 'bg-[#2c1b0b] text-white shadow-[0_12px_24px_rgba(44,27,11,0.12)]'
        : 'text-honey-nav hover:bg-[#2c1b0b]/5 hover:text-honey-orange';
    $localePill = fn (string $locale) => $currentLocale === $locale
        ? 'bg-honey-orange text-white shadow-[0_12px_24px_rgba(199,72,23,0.18)]'
        : 'text-honey-nav hover:bg-[#2c1b0b]/5 hover:text-honey-orange';
@endphp

<header class="fixed left-0 right-0 top-0 z-50 border-b border-[#2c1b0b]/8 bg-[linear-gradient(180deg,rgba(251,247,241,0.94),rgba(251,247,241,0.82))] backdrop-blur-xl shadow-[0_1px_0_rgba(44,27,11,0.04)] transition-shadow duration-300" id="site-header">
    <div class="mx-auto w-full max-w-[1400px] px-4 py-3 sm:px-6 lg:px-8 lg:py-4 xl:px-10">
        <div class="flex h-[64px] items-center justify-between rounded-[1.7rem] border border-[#2c1b0b]/8 bg-white/62 px-4 shadow-[0_16px_34px_rgba(44,27,11,0.06)] backdrop-blur-md sm:px-5 lg:h-[72px] lg:px-6">
            <a href="{{ route('home') }}" class="flex flex-shrink-0 items-center rounded-full transition-opacity hover:opacity-80">
                <img src="{{ asset('images/honey_logo_2.png') }}"
                     alt="{{ __('home.brand.logo_alt') }}"
                     class="h-11 w-auto sm:h-12 lg:h-[3.05rem]"
                     width="140"
                     height="56">
            </a>

            <nav class="hidden lg:flex items-center gap-1" aria-label="Main navigation">
                <a href="{{ route('home') }}"
                   class="rounded-full px-4 py-2.5 font-condensed text-[13px] font-bold uppercase tracking-[0.2em] transition-all duration-200 xl:px-5 {{ $desktopNavItem('home') }}">
                    {{ __('home.nav.home') }}
                </a>
                <a href="{{ route('about') }}"
                   class="rounded-full px-4 py-2.5 font-condensed text-[13px] font-bold uppercase tracking-[0.2em] transition-all duration-200 xl:px-5 {{ $desktopNavItem('about') }}">
                    {{ __('home.nav.about') }}
                </a>
                <a href="{{ route('home') }}#products"
                   class="rounded-full px-4 py-2.5 font-condensed text-[13px] font-bold uppercase tracking-[0.2em] transition-all duration-200 xl:px-5 {{ $desktopNavItem('products.index') }}">
                    {{ __('home.nav.products') }}
                </a>
                <a href="{{ route('faq') }}"
                   class="rounded-full px-4 py-2.5 font-condensed text-[13px] font-bold uppercase tracking-[0.2em] transition-all duration-200 xl:px-5 {{ $desktopNavItem('faq') }}">
                    {{ __('home.nav.faq') }}
                </a>
                <a href="{{ route('contact') }}"
                   class="rounded-full px-4 py-2.5 font-condensed text-[13px] font-bold uppercase tracking-[0.2em] transition-all duration-200 xl:px-5 {{ $desktopNavItem('contact') }}">
                    {{ __('home.nav.contact') }}
                </a>
            </nav>

            <div class="hidden lg:flex items-center gap-4 xl:gap-5">
                <div class="flex items-center gap-1 rounded-full border border-[#2c1b0b]/8 bg-[#fbf7f1]/88 p-1 font-condensed text-xs font-bold uppercase tracking-[0.2em] text-honey-nav">
                    <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                       class="rounded-full px-3 py-2 transition-all duration-200 {{ $localePill('en') }}">
                        {{ __('home.nav.languages.en') }}
                    </a>
                    <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                       class="rounded-full px-3 py-2 transition-all duration-200 {{ $localePill('ar') }}">
                        {{ __('home.nav.languages.ar') }}
                    </a>
                </div>

                <a href="{{ route('contact') }}"
                   class="inline-flex h-[46px] items-center rounded-full bg-honey-orange px-7 font-condensed text-[13px] font-bold uppercase tracking-[0.18em] text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#b64014] hover:shadow-[0_14px_28px_rgba(199,72,23,0.22)]">
                    {{ __('home.nav.contact') }}
                </a>
            </div>

            <button id="mobile-menu-btn"
                    class="relative h-11 w-11 rounded-full border border-[#2c1b0b]/8 bg-[#fbf7f1]/86 transition-colors hover:bg-white lg:hidden"
                    aria-label="{{ __('home.nav.toggle_menu') }}"
                    aria-expanded="false"
                    aria-controls="mobile-menu">
                <svg id="icon-open" xmlns="http://www.w3.org/2000/svg" class="mx-auto h-5 w-5 text-honey-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg id="icon-close" xmlns="http://www.w3.org/2000/svg" class="mx-auto h-5 w-5 hidden text-honey-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div id="mobile-menu"
         class="hidden overflow-hidden transition-all duration-300 lg:hidden"
         style="max-height: 0;">
        <div class="px-4 pb-3 sm:px-6 lg:px-8 xl:px-10">
            <div class="rounded-[1.6rem] border border-[#2c1b0b]/8 bg-white/80 p-3 shadow-[0_16px_34px_rgba(44,27,11,0.06)] backdrop-blur-md">
                <nav class="flex flex-col gap-1" aria-label="Mobile navigation">
                    <a href="{{ route('home') }}" class="rounded-[1rem] px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-all {{ $mobileNavItem('home') }}">
                        {{ __('home.nav.home') }}
                    </a>
                    <a href="{{ route('about') }}" class="rounded-[1rem] px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-all {{ $mobileNavItem('about') }}">
                        {{ __('home.nav.about') }}
                    </a>
                    <a href="{{ route('home') }}#products" class="rounded-[1rem] px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-all {{ $mobileNavItem('products.index') }}">
                        {{ __('home.nav.products') }}
                    </a>
                    <a href="{{ route('faq') }}" class="rounded-[1rem] px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-all {{ $mobileNavItem('faq') }}">
                        {{ __('home.nav.faq') }}
                    </a>
                    <a href="{{ route('contact') }}" class="rounded-[1rem] px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-all {{ $mobileNavItem('contact') }}">
                        {{ __('home.nav.contact') }}
                    </a>
                </nav>

                <div class="mt-4 flex items-center justify-between border-t border-[#2c1b0b]/8 pt-4">
                    <div class="flex items-center gap-2 rounded-full border border-[#2c1b0b]/8 bg-[#fbf7f1] p-1 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav">
                        <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                           class="rounded-full px-3 py-1.5 transition-all {{ $localePill('en') }}">
                            {{ __('home.nav.languages.en') }}
                        </a>
                        <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                           class="rounded-full px-3 py-1.5 transition-all {{ $localePill('ar') }}">
                            {{ __('home.nav.languages.ar') }}
                        </a>
                    </div>
                    <a href="{{ route('contact') }}"
                       class="rounded-full bg-honey-orange px-6 py-2.5 font-condensed font-bold text-xs uppercase tracking-widest text-white transition-all hover:bg-[#b64014] hover:shadow-[0_12px_24px_rgba(199,72,23,0.2)]">
                        {{ __('home.nav.contact') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="h-[88px] lg:h-[104px]"></div>

<script>
(function () {
    var btn = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    var iconOpen = document.getElementById('icon-open');
    var iconClose = document.getElementById('icon-close');

    btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));

        if (expanded) {
            menu.style.maxHeight = '0';
            setTimeout(function () { menu.classList.add('hidden'); }, 300);
        } else {
            menu.classList.remove('hidden');
            menu.style.maxHeight = menu.scrollHeight + 'px';
        }

        iconOpen.classList.toggle('hidden');
        iconClose.classList.toggle('hidden');
    });

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            btn.setAttribute('aria-expanded', 'false');
            menu.style.maxHeight = '0';
            setTimeout(function () { menu.classList.add('hidden'); }, 300);
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
        });
    });
}());
</script>