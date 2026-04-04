{{-- resources/views/components/navbar.blade.php --}}
@php($currentLocale = app()->currentLocale())
<header class="fixed left-0 right-0 top-0 z-50 min-h-[96px] bg-honey-cream/95 backdrop-blur-sm shadow-sm xl:min-h-[117px]" id="site-header">
    <div class="mx-auto flex min-h-[96px] w-full max-w-[1900px] items-center justify-between px-6 sm:px-8 xl:min-h-[117px] xl:px-11">

        {{-- Logo --}}
        <a href="{{ url('/') }}" class="flex-shrink-0">
              <img src="{{ asset('images/honey_logo_2.png') }}"
                  alt="{{ __('home.brand.logo_alt') }}"
                  class=" w-auto sm:h-14 xl:h-[28px]"
                  style="height: 4.5rem">
        </a>

        {{-- Desktop Navigation --}}
        <nav class="hidden lg:flex items-center gap-1 xl:gap-2">
            <a href="{{ route('home') }}"
               class="px-5 py-1 font-condensed text-[15px] font-bold uppercase tracking-[0.22em] text-honey-orange transition-colors hover:text-honey-orange/80 xl:px-7">
                {{ __('home.nav.home') }}
            </a>
            <a href="{{ url('/pages') }}"
               class="px-5 py-1 font-condensed text-[15px] font-bold uppercase tracking-[0.22em] text-honey-nav transition-colors hover:text-honey-orange xl:px-7">
                {{ __('home.nav.pages') }}
            </a>
            <a href="{{ route('home') }}#products"
               class="px-5 py-1 font-condensed text-[15px] font-bold uppercase tracking-[0.22em] text-honey-nav transition-colors hover:text-honey-orange xl:px-7">
                {{ __('home.nav.products') }}
            </a>
            <a href="{{ url('/blog') }}"
               class="px-5 py-1 font-condensed text-[15px] font-bold uppercase tracking-[0.22em] text-honey-nav transition-colors hover:text-honey-orange xl:px-7">
                {{ __('home.nav.blog') }}
            </a>
            <a href="{{ url('/portfolio') }}"
               class="px-5 py-1 font-condensed text-[15px] font-bold uppercase tracking-[0.22em] text-honey-nav transition-colors hover:text-honey-orange xl:px-7">
                {{ __('home.nav.portfolio') }}
            </a>
        </nav>

        {{-- Actions: Cart + Contact --}}
        <div class="hidden lg:flex items-center gap-6 xl:gap-8">
            <div class="flex items-center gap-2 font-condensed text-xs font-bold uppercase tracking-[0.22em] text-honey-nav">
                <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                   class="transition-colors {{ $currentLocale === 'en' ? 'text-honey-orange' : 'hover:text-honey-orange' }}">
                    {{ __('home.nav.languages.en') }}
                </a>
                <span class="text-black/20">/</span>
                <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                   class="transition-colors {{ $currentLocale === 'ar' ? 'text-honey-orange' : 'hover:text-honey-orange' }}">
                    {{ __('home.nav.languages.ar') }}
                </a>
            </div>

            {{-- Cart Icon --}}
            <a href="{{ url('/cart') }}" class="relative p-2 text-honey-muted transition-colors hover:text-honey-orange">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="absolute right-0 top-0 flex h-4 w-4 items-center justify-center rounded-full bg-honey-orange font-condensed text-[10px] font-bold leading-none text-white">
                    0
                </span>
            </a>

            {{-- Contact Button --}}
            <a href="{{ url('/contact') }}"
               class="inline-flex min-h-[52px] items-center bg-honey-gold px-10 py-3 font-condensed text-[15px] font-bold uppercase tracking-[0.2em] text-white transition-opacity hover:opacity-90 xl:px-11">
                {{ __('home.nav.contact') }}
            </a>
        </div>

        {{-- Mobile Hamburger --}}
        <button id="mobile-menu-btn"
                class="lg:hidden p-2 text-honey-dark"
            aria-label="{{ __('home.nav.toggle_menu') }}"
                aria-expanded="false"
                aria-controls="mobile-menu">
            <svg id="icon-open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg id="icon-close" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu"
         class="lg:hidden hidden bg-honey-cream border-t border-black/10 px-6 pb-6">
        <nav class="flex flex-col gap-1 pt-4">
            <a href="{{ route('home') }}"       class="py-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-orange">{{ __('home.nav.home') }}</a>
            <a href="{{ url('/pages') }}"  class="py-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav hover:text-honey-orange">{{ __('home.nav.pages') }}</a>
            <a href="{{ route('home') }}#products" class="py-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav hover:text-honey-orange">{{ __('home.nav.products') }}</a>
            <a href="{{ url('/blog') }}"   class="py-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav hover:text-honey-orange">{{ __('home.nav.blog') }}</a>
            <a href="{{ url('/portfolio') }}" class="py-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav hover:text-honey-orange">{{ __('home.nav.portfolio') }}</a>
            <div class="flex items-center gap-3 pt-2 font-condensed font-bold text-sm uppercase tracking-widest text-honey-nav">
                <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                   class="transition-colors {{ $currentLocale === 'en' ? 'text-honey-orange' : 'hover:text-honey-orange' }}">{{ __('home.nav.languages.en') }}</a>
                <a href="{{ route('locale.switch', ['locale' => 'ar']) }}"
                   class="transition-colors {{ $currentLocale === 'ar' ? 'text-honey-orange' : 'hover:text-honey-orange' }}">{{ __('home.nav.languages.ar') }}</a>
            </div>
        </nav>
        <div class="flex items-center gap-4 pt-4">
            <a href="{{ url('/cart') }}" class="relative text-honey-muted hover:text-honey-orange transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </a>
            <a href="{{ url('/contact') }}"
               class="px-7 py-2 bg-honey-gold font-condensed font-bold text-sm uppercase tracking-widest text-white hover:opacity-90">
                {{ __('home.nav.contact') }}
            </a>
        </div>
    </div>
</header>

{{-- Spacer to offset fixed header --}}
<div class="h-[96px] xl:h-[117px]"></div>

<script>
(function () {
    var btn  = document.getElementById('mobile-menu-btn');
    var menu = document.getElementById('mobile-menu');
    var iconOpen  = document.getElementById('icon-open');
    var iconClose = document.getElementById('icon-close');

    btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));
        menu.classList.toggle('hidden');
        iconOpen.classList.toggle('hidden');
        iconClose.classList.toggle('hidden');
    });
}());
</script>
