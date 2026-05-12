{{-- resources/views/components/products.blade.php --}}
@props([
    'products' => []
])

@php
    $productsBackground = asset('images/Section2.png');
    $productCount = collect($products)->count();
    $isRtl = app()->isLocale('ar');
    $isCompactTwoProductLayout = $productCount === 2;
    $productGridClasses = match (true) {
        $productCount <= 1 => 'mx-auto max-w-[34rem] grid-cols-1',
        $isCompactTwoProductLayout => 'mx-auto max-w-[1080px] grid-cols-1 gap-5 lg:grid-cols-2 lg:gap-6',
        default => 'grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7',
    };
@endphp

<style>
    @media (max-width: 1023px) {
        #products .products-card--compact {
            gap: 1.25rem;
            padding: 1.25rem;
        }

        #products .products-card--compact .products-card-body {
            padding-top: 0.55rem;
            padding-bottom: 14px;
        }

        #products .products-card--compact .products-card-cta {
            margin-bottom: 6px;
        }
    }

    @media (min-width: 768px) {
        #products .products-card--compact {
            flex-direction: row;
            align-items: stretch;
        }

        #products .products-card--compact .products-card-media {
            flex: 0 0 39%;
            width: 39%;
            min-width: 240px;
            max-width: 300px;
            min-height: 250px;
        }

        #products .products-card--compact .products-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #products .products-card--compact .products-card-body {
            justify-content: center;
            padding-block: 0.5rem;
        }
    }
</style>

<section id="products" class="relative isolate scroll-mt-[100px] overflow-hidden bg-[#fbf7f1] py-18 lg:scroll-mt-[116px] lg:py-24">

    <img src="{{ $productsBackground }}"
         alt=""
         aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-[1] h-full w-full object-cover object-center select-none">

    {{-- Overlay --}}
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_top,_rgba(246,193,90,0.18),_transparent_36%),linear-gradient(180deg,#fbf7f1_0%,#fbf7f1_8%,rgba(255,244,223,0.96)_22%,rgba(255,236,205,0.88)_44%,rgba(251,247,241,0.96)_100%)]"></div>

        <div class="relative z-[2] max-w-[1300px] mx-auto px-6">
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
                <article class="home-surface-soft home-card-hover group overflow-hidden {{ $isCompactTwoProductLayout ? 'products-card--compact flex flex-col gap-4 p-3.5 sm:p-4' : 'flex flex-col p-4 sm:p-5' }}">

                {{-- Product Image --}}
                <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                       class="products-card-media relative block overflow-hidden rounded-[1.6rem] border border-[#2c1b0b]/8 bg-[#f5eee6] shadow-[0_14px_30px_rgba(44,27,11,0.06)] transition-shadow duration-300 group-hover:shadow-[0_18px_40px_rgba(44,27,11,0.12)] {{ $isCompactTwoProductLayout ? 'aspect-[5/3.6] md:aspect-auto' : 'aspect-[4/4.5]' }}">
                    <img src="{{ asset($product['image']) }}"
                         alt="{{ $product['name'] }}"
                         loading="lazy"
                         decoding="async"
                        class="products-card-image h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">

                    {{-- Badge --}}
                    @if ($product['badge'])
                    <span class="absolute top-5 {{ app()->isLocale('ar') ? 'left-5' : 'right-5' }} rounded-full border border-white/70 bg-white/88 px-3 py-1 font-condensed text-[11px] font-bold uppercase tracking-widest text-[#c74817] shadow-[0_8px_20px_rgba(44,27,11,0.08)]">
                        {{ $product['badge'] }}
                    </span>
                    @endif

                    @if (filled($product['size'] ?? null))
                    <span class="absolute inline-flex items-center rounded-full border border-white/75 bg-white/90 font-condensed text-[0.72rem] font-bold uppercase tracking-[0.18em] text-[#2c1b0b] shadow-[0_10px_24px_rgba(44,27,11,0.1)] backdrop-blur-sm" @if($isRtl) style="right: 1.2rem; bottom: 1.2rem; padding: 0.42rem 0.82rem;" @else style="left: 1.2rem; bottom: 1.2rem; padding: 0.42rem 0.82rem;" @endif>
                        {{ $product['size'] }}
                    </span>
                    @endif

                    {{-- Hover overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>

                {{-- Product Info --}}
                <div class="products-card-body flex flex-1 flex-col gap-3 {{ $isCompactTwoProductLayout ? ($isRtl ? 'items-center pt-1 text-center md:items-end md:text-right' : 'items-center pt-1 text-center md:items-start md:text-left') : 'items-center pt-6 text-center' }}">
                    <h3 class="font-condensed font-bold uppercase leading-tight text-honey-dark transition-colors group-hover:text-honey-orange {{ $isCompactTwoProductLayout ? 'text-[1.3rem] md:text-[1.22rem]' : 'text-[1.45rem]' }}">
                        <a href="{{ route('products.show', ['slug' => $product['slug']]) }}">{{ $product['name'] }}</a>
                    </h3>
                    <p class="font-condensed text-xl font-bold text-honey-orange">
                        {{ $product['price'] }}
                    </p>
                          <p class="text-sm leading-7 text-honey-muted {{ $isCompactTwoProductLayout ? 'max-w-[34ch] md:max-w-none' : 'max-w-[280px]' }}">
                        {{ Str::limit($product['excerpt'], $isCompactTwoProductLayout ? 72 : 90) }}
                    </p>
                    <a href="{{ route('products.show', ['slug' => $product['slug']]) }}"
                            class="products-card-cta inline-flex items-center gap-2.5 rounded-full border border-honey-dark/15 px-5 py-2.5 font-condensed text-xs font-bold uppercase tracking-widest text-honey-dark transition-all duration-200 hover:border-honey-orange hover:bg-honey-orange hover:text-white hover:shadow-[0_10px_24px_rgba(199,72,23,0.2)] {{ $isCompactTwoProductLayout ? 'mt-2 md:mt-4' : 'mt-auto' }}">
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
