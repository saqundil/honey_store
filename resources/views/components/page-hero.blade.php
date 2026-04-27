{{-- resources/views/components/page-hero.blade.php --}}
@props([
    'eyebrow' => '',
    'heading' => '',
])

@php($titleBackground = asset('images/product-figma/title-bg.png'))

<section class="figma-product-title">
    <img src="{{ $titleBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-cover object-center select-none"
         decoding="async">
    <div class="absolute inset-0 bg-[linear-gradient(rgba(52,30,12,0.38),rgba(52,30,12,0.22))]"></div>
    <div class="relative mx-auto flex min-h-[clamp(14rem,28vw,20rem)] max-w-[1130px] flex-col items-center justify-center px-6 py-14 text-center text-white sm:py-16">
        @if ($eyebrow)
            <p class="font-condensed text-sm font-bold uppercase tracking-[0.28em] text-white/85">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 max-w-[16ch] font-condensed text-[clamp(2.4rem,7vw,4rem)] font-bold uppercase leading-[0.94] drop-shadow-[0_14px_34px_rgba(0,0,0,0.18)]">
            {{ $heading }}
        </h1>
    </div>
</section>
