{{-- resources/views/components/products.blade.php --}}
@props([
    'products' => []
])

@php
    $productsBackground = asset('images/Section2.png');
    $productCount = collect($products)->count();
    $productGridClasses = match (true) {
        $productCount <= 1 => 'mx-auto max-w-[34rem] grid-cols-1',
        $productCount === 2 => 'mx-auto max-w-[980px] grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-2 lg:gap-8',
        default => 'grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7',
    };
@endphp

<section id="products" class="relative scroll-mt-[100px] overflow-hidden bg-[#fbf7f1] py-18 lg:scroll-mt-[116px] lg:py-24">

    <img src="{{ $productsBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center opacity-30 select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(246,193,90,0.18),_transparent_36%),linear-gradient(180deg,#fbf7f1_0%,#fbf7f1_8%,rgba(255,244,223,0.96)_22%,rgba(255,236,205,0.88)_44%,rgba(251,247,241,0.96)_100%)]"></div>

    <div class="relative max-w-[1300px] mx-auto px-6">
        <div class="home-surface relative overflow-hidden px-6 py-8 sm:p-8 lg:p-10">
            <div class="mb-14 flex flex-col gap-4 text-center">
                <span class="home-eyebrow mx-auto">{{ app()->isLocale('ar') ? 'مختاراتنا' : 'Our selection' }}</span>
                <h2 class="home-title mx-auto max-w-[12ch]">{{ __('home.products.heading') }}</h2>
                <p class="home-copy mx-auto max-w-2xl">
                    {{ __('home.products.description') }}
                </p>
            </div>

            {{-- Product Grid --}}
            <div class="grid {{ $productGridClasses }}">
            @foreach ($products as $product)
            <article class="home-surface-soft home-card-hover flex flex-col overflow-hidden p-4 sm:p-5 group">

                {{-- Product Image --}}
                <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                   class="relative block aspect-[4/4.5] overflow-hidden rounded-[1.6rem] border border-[#2c1b0b]/8 bg-[#f5eee6] shadow-[0_14px_30px_rgba(44,27,11,0.06)] transition-shadow duration-300 group-hover:shadow-[0_18px_40px_rgba(44,27,11,0.12)]">
                    <img src="{{ asset($product['image']) }}"
                         alt="{{ $product['name'] }}"
                         loading="lazy"
                         decoding="async"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                    {{-- Badge --}}
                    @if ($product['badge'])
                    <span class="absolute top-5 {{ app()->isLocale('ar') ? 'left-5' : 'right-5' }} rounded-full border border-white/70 bg-white/88 px-3 py-1 font-condensed text-[11px] font-bold uppercase tracking-widest text-[#c74817] shadow-[0_8px_20px_rgba(44,27,11,0.08)]">
                        {{ $product['badge'] }}
                    </span>
                    @endif

                    {{-- Hover overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>

                {{-- Product Info --}}
                <div class="flex flex-1 flex-col items-center gap-3 pt-6 text-center">
                    <h3 class="font-condensed text-[1.45rem] font-bold uppercase leading-tight text-honey-dark transition-colors group-hover:text-honey-orange">
                        <a href="{{ route('products.show', ['slug' => $product['slug']]) }}">{{ $product['name'] }}</a>
                    </h3>
                    <p class="font-condensed text-xl font-bold text-honey-orange">
                        {{ $product['price'] }}
                    </p>
                    <p class="max-w-[280px] text-sm leading-7 text-honey-muted">
                        {{ Str::limit($product['excerpt'], 90) }}
                    </p>
                    <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                       class="mt-auto inline-flex items-center gap-2.5 rounded-full border border-honey-dark/15 px-5 py-2.5 font-condensed text-xs font-bold uppercase tracking-widest text-honey-dark transition-all duration-200 hover:bg-honey-orange hover:text-white hover:border-honey-orange hover:shadow-[0_10px_24px_rgba(199,72,23,0.2)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        {{ __('home.products.view_and_order') }}
                    </a>
                </div>

            </article>
            @endforeach
            </div>
        </div>

    </div>
</section>
