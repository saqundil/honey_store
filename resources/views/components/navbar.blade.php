{{-- resources/views/components/navbar.blade.php --}}
@php
    $currentLocale = app()->currentLocale();
    $currentRoute = Route::currentRouteName();
    $isActive = fn (string ...$routes) => collect($routes)->contains($currentRoute) ? 'text-honey-orange' : 'text-honey-nav hover:text-honey-orange';
@endphp
<header class="fixed left-0 right-0 top-0 z-50 bg-honey-cream/95 backdrop-blur-md shadow-[0_1px_0_rgba(0,0,0,0.06)] transition-shadow duration-300" id="site-header">
    <div class="mx-auto flex h-[80px] w-full max-w-[1400px] items-center justify-between px-5 sm:px-8 lg:h-[96px] xl:px-10">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex-shrink-0 transition-opacity hover:opacity-80">
            <img src="{{ asset('images/honey_logo_2.png') }}"
                 alt="{{ __('home.brand.logo_alt') }}"
                 class="h-12 w-auto sm:h-14 lg:h-[3.2rem]"
                 width="140" height="56">
        </a>

        {{-- Desktop Navigation --}}
        <nav class="hidden lg:flex items-center gap-0.5 xl:gap-1" aria-label="Main navigation">
            <a href="{{ route('home') }}"
               class="px-4 py-2 font-condensed text-[14px] font-bold uppercase tracking-[0.2em] transition-colors xl:px-5 {{ $isActive('home') }}">
                {{ __('home.nav.home') }}
            </a>
            <a href="{{ route('about') }}"
               class="px-4 py-2 font-condensed text-[14px] font-bold uppercase tracking-[0.2em] transition-colors xl:px-5 {{ $isActive('about') }}">
                {{ __('home.nav.about') }}
            </a>
            <a href="{{ route('home') }}#products"
               class="px-4 py-2 font-condensed text-[14px] font-bold uppercase tracking-[0.2em] transition-colors xl:px-5 {{ $isActive('products.index') }}">
                {{ __('home.nav.products') }}
            </a>
            <a href="{{ route('faq') }}"
               class="px-4 py-2 font-condensed text-[14px] font-bold uppercase tracking-[0.2em] transition-colors xl:px-5 {{ $isActive('faq') }}">
                {{ __('home.nav.faq') }}
            </a>
            <a href="{{ route('contact') }}"
               class="px-4 py-2 font-condensed text-[14px] font-bold uppercase tracking-[0.2em] transition-colors xl:px-5 {{ $isActive('contact') }}">
                {{ __('home.nav.contact') }}
            </a>
        </nav>

        {{-- Actions --}}
        <div class="hidden lg:flex items-center gap-5 xl:gap-6">
            {{-- Language Switcher --}}
            <div class="flex items-center gap-1.5 font-condensed text-xs font-bold uppercase tracking-[0.2em] text-honey-nav">
                <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                   class="rounded-full px-2.5 py-1.5 transition-all duration-200 {{ $currentLocale === 'en' ? 'bg-honey-orange/10 text-honey-orange' : 'hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.languages.en') }}
                </a>
                <span class="text-black/15">|</span>
                <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                   class="rounded-full px-2.5 py-1.5 transition-all duration-200 {{ $currentLocale === 'ar' ? 'bg-honey-orange/10 text-honey-orange' : 'hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.languages.ar') }}
                </a>
            </div>

            {{-- Contact Button --}}
            <a href="{{ route('contact') }}"
               class="inline-flex h-[48px] items-center bg-honey-gold px-8 font-condensed text-[13px] font-bold uppercase tracking-[0.18em] text-white transition-all duration-200 hover:bg-honey-gold/90 hover:shadow-[0_8px_24px_rgba(211,168,99,0.3)]">
                {{ __('home.nav.contact') }}
            </a>
        </div>

        {{-- Mobile Hamburger --}}
        <button id="mobile-menu-btn"
                class="lg:hidden relative h-10 w-10 rounded-lg transition-colors hover:bg-black/5"
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

    {{-- Mobile Menu --}}
    <div id="mobile-menu"
         class="lg:hidden hidden overflow-hidden transition-all duration-300"
         style="max-height: 0;">
        <div class="border-t border-black/8 bg-honey-cream px-5 pb-6 pt-4 sm:px-8">
            <nav class="flex flex-col gap-0.5" aria-label="Mobile navigation">
                <a href="{{ route('home') }}" class="rounded-lg px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-colors {{ $currentRoute === 'home' ? 'bg-honey-orange/8 text-honey-orange' : 'text-honey-nav hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.home') }}
                </a>
                <a href="{{ route('about') }}" class="rounded-lg px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-colors {{ $currentRoute === 'about' ? 'bg-honey-orange/8 text-honey-orange' : 'text-honey-nav hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.about') }}
                </a>
                <a href="{{ route('home') }}#products" class="rounded-lg px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-colors text-honey-nav hover:bg-honey-orange/5 hover:text-honey-orange">
                    {{ __('home.nav.products') }}
                </a>
                <a href="{{ route('faq') }}" class="rounded-lg px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-colors {{ $currentRoute === 'faq' ? 'bg-honey-orange/8 text-honey-orange' : 'text-honey-nav hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.faq') }}
                </a>
                <a href="{{ route('contact') }}" class="rounded-lg px-4 py-3 font-condensed font-bold text-sm uppercase tracking-widest transition-colors {{ $currentRoute === 'contact' ? 'bg-honey-orange/8 text-honey-orange' : 'text-honey-nav hover:bg-honey-orange/5 hover:text-honey-orange' }}">
                    {{ __('home.nav.contact') }}
                </a>
            </nav>

            <div class="mt-4 flex items-center justify-between border-t border-black/8 pt-4">
                <div class="flex items-center gap-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav">
                    <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                       class="rounded-full px-3 py-1.5 transition-all {{ $currentLocale === 'en' ? 'bg-honey-orange/10 text-honey-orange' : 'hover:text-honey-orange' }}">
                        {{ __('home.nav.languages.en') }}
                    </a>
                    <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                       class="rounded-full px-3 py-1.5 transition-all {{ $currentLocale === 'ar' ? 'bg-honey-orange/10 text-honey-orange' : 'hover:text-honey-orange' }}">
                        {{ __('home.nav.languages.ar') }}
                    </a>
                </div>
                <a href="{{ route('contact') }}"
                   class="px-6 py-2.5 bg-honey-gold font-condensed font-bold text-xs uppercase tracking-widest text-white transition-opacity hover:opacity-90">
                    {{ __('home.nav.contact') }}
                </a>
            </div>
        </div>
    </div>
</header>

{{-- Spacer to offset fixed header --}}
<div class="h-[80px] lg:h-[96px]"></div>

<script>
(function () {
    var btn  = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    var iconOpen  = document.getElementById('icon-open');
    var iconClose = document.getElementById('icon-close');

    btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));

        if (expanded) {
            menu.style.maxHeight = '0';
            setTimeout(function() { menu.classList.add('hidden'); }, 300);
        } else {
            menu.classList.remove('hidden');
            menu.style.maxHeight = menu.scrollHeight + 'px';
        }

        iconOpen.classList.toggle('hidden');
        iconClose.classList.toggle('hidden');
    });

    menu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            btn.setAttribute('aria-expanded', 'false');
            menu.style.maxHeight = '0';
            setTimeout(function() { menu.classList.add('hidden'); }, 300);
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
        });
    });
}());
</script>
