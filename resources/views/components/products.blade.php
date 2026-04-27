{{-- resources/views/components/products.blade.php --}}
@props([
    'products' => []
])

@php($productsBackground = asset('images/Section.png'))

<section id="products" class="relative scroll-mt-[100px] overflow-hidden bg-honey-cream py-20 lg:scroll-mt-[116px] lg:py-28">

    <img src="{{ $productsBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-honey-cream/42"></div>

    <div class="relative max-w-[1300px] mx-auto px-6">

        {{-- Section Header --}}
        <div class="text-center mb-14 flex flex-col gap-4">
            <h2 class="font-condensed font-bold text-4xl md:text-5xl uppercase text-honey-dark leading-tight">
                {{ __('home.products.heading') }}
            </h2>
            <p class="text-honey-grey text-base md:text-lg leading-relaxed max-w-2xl mx-auto">
                {{ __('home.products.description') }}
            </p>
        </div>

        {{-- Product Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
            @foreach ($products as $product)
            <article class="flex flex-col group">

                {{-- Product Image --}}
                <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                   class="relative overflow-hidden aspect-[4/4.5] bg-honey-card rounded-[1.5rem] block shadow-[0_12px_30px_rgba(44,27,11,0.06)] transition-shadow duration-300 group-hover:shadow-[0_18px_40px_rgba(44,27,11,0.12)]">
                    <img src="{{ asset($product['image']) }}"
                         alt="{{ $product['name'] }}"
                         loading="lazy"
                         decoding="async"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                    {{-- Badge --}}
                    @if ($product['badge'])
                    <span class="absolute top-5 {{ app()->isLocale('ar') ? 'left-5' : 'right-5' }} rounded-full bg-honey-orange px-3 py-1 font-condensed text-[11px] font-bold uppercase tracking-widest text-white shadow-[0_8px_20px_rgba(199,72,23,0.25)]">
                        {{ $product['badge'] }}
                    </span>
                    @endif

                    {{-- Hover overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>

                {{-- Product Info --}}
                <div class="flex flex-col items-center gap-2.5 pt-6 text-center">
                    <h3 class="font-condensed text-[1.4rem] font-bold uppercase leading-tight text-honey-dark transition-colors group-hover:text-honey-orange">
                        <a href="{{ route('products.show', ['slug' => $product['slug']]) }}">{{ $product['name'] }}</a>
                    </h3>
                    <p class="font-condensed text-xl font-bold text-honey-orange">
                        {{ $product['price'] }}
                    </p>
                    <p class="text-honey-muted text-sm leading-relaxed max-w-[280px]">
                        {{ Str::limit($product['excerpt'], 90) }}
                    </p>
                    <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                       class="mt-3 inline-flex items-center gap-2.5 rounded-full border border-honey-dark/15 px-5 py-2.5 font-condensed text-xs font-bold uppercase tracking-widest text-honey-dark transition-all duration-200 hover:bg-honey-orange hover:text-white hover:border-honey-orange hover:shadow-[0_10px_24px_rgba(199,72,23,0.2)]">
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
</section>
