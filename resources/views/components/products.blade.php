{{-- resources/views/components/products.blade.php --}}
@props([
    'products' => []
])

@php($productsBackground = asset('images/Section.png'))

<section id="products" class="relative scroll-mt-[116px] overflow-hidden bg-honey-cream py-20 xl:scroll-mt-[137px] lg:py-28">

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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10 lg:gap-16">
            @foreach ($products as $product)
            <article class="flex flex-col gap-4 group">

                {{-- Product Image --}}
                <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                   class="relative overflow-hidden aspect-square bg-honey-card block">
                    <img src="{{ asset($product['image']) }}"
                         alt="{{ $product['name'] }}"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

                    {{-- Badge --}}
                    @if ($product['badge'])
                    <span class="absolute top-6 right-6 bg-honey-orange text-white font-condensed font-bold text-xs uppercase tracking-widest px-2 py-0.5">
                        {{ $product['badge'] }}
                    </span>
                    @endif

                    {{-- Hover overlay --}}
                    <div class="absolute inset-0 bg-white/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>

                {{-- Product Info --}}
                <div class="flex flex-col items-center gap-2 text-center">
                    <h3 class="font-condensed font-bold text-2xl uppercase text-honey-dark leading-tight hover:text-honey-orange transition-colors">
                        <a href="{{ route('products.show', ['slug' => $product['slug']]) }}">{{ $product['name'] }}</a>
                    </h3>
                    <p class="font-condensed font-bold text-xl text-honey-orange">
                        {{ $product['price'] }}
                    </p>
                    <p class="text-honey-muted text-sm leading-relaxed max-w-xs">
                        {{ $product['excerpt'] }}
                    </p>
                    <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                       class="mt-2 inline-flex items-center gap-2 font-condensed font-bold text-xs uppercase tracking-widest text-honey-dark border border-honey-dark/20 px-4 py-2 hover:bg-honey-orange hover:text-white hover:border-honey-orange transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
