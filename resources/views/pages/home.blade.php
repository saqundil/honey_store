{{-- resources/views/pages/home.blade.php --}}
@extends('layouts.app')

@section('content')

    {{-- 1. Hero --}}
    <x-hero />

    {{-- 2. Trust Badges --}}
    <section class="border-b border-black/5 bg-white py-8 lg:py-10">
        <div class="mx-auto grid max-w-[1300px] grid-cols-2 gap-6 px-6 md:grid-cols-4">
            @php
                $trustItems = [
                    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => app()->isLocale('ar') ? 'عسل نقي 100%' : '100% Pure Honey'],
                    ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => app()->isLocale('ar') ? 'مصدر طبيعي' : 'Natural Origin'],
                    ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'label' => app()->isLocale('ar') ? 'تغليف آمن' : 'Secure Packaging'],
                    ['icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'label' => app()->isLocale('ar') ? 'مصنوع بحب' : 'Made with Care'],
                ];
            @endphp
            @foreach ($trustItems as $trustItem)
                <div class="flex items-center justify-center gap-3 py-2">
                    <svg class="h-5 w-5 flex-shrink-0 text-honey-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $trustItem['icon'] }}"/>
                    </svg>
                    <span class="font-condensed text-xs font-bold uppercase tracking-[0.16em] text-honey-dark">{{ $trustItem['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3. Mission + News --}}
    <section class="py-16 lg:py-24 bg-honey-cream">
        <div class="max-w-[1300px] mx-auto px-6">
            <div class="flex flex-col lg:flex-row gap-10 lg:gap-0">
                <x-mission />
                <x-news />
            </div>
        </div>
    </section>

    {{-- 4. Products --}}
    <x-products :products="$products" />

    {{-- 5. Testimonial + Gallery --}}
    <x-testimonial />

    {{-- 6. Newsletter + Types of Honey --}}
    <x-newsletter />

    {{-- 7. About Teaser / CTA Banner --}}
    <section class="relative overflow-hidden bg-honey-dark py-16 lg:py-20">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(211,168,99,0.08),transparent_60%)]"></div>
        <div class="relative mx-auto max-w-[1000px] px-6 text-center">
            <p class="font-condensed text-sm font-bold uppercase tracking-[0.3em] text-honey-gold">
                {{ app()->isLocale('ar') ? 'من جبال قرغيزستان' : 'From the Mountains of Kyrgyzstan' }}
            </p>
            <h2 class="mt-4 font-condensed text-3xl font-bold uppercase text-white md:text-4xl lg:text-5xl">
                {{ app()->isLocale('ar') ? 'عسل نقي بتراث عريق' : 'Pure Honey. Rich Heritage.' }}
            </h2>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-8 text-white/70 md:text-lg">
                {{ __('home.hero.description') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('about') }}"
                   class="inline-flex items-center gap-2 bg-honey-gold px-8 py-3.5 font-condensed text-sm font-bold uppercase tracking-[0.18em] text-white transition-all duration-200 hover:opacity-90">
                    {{ app()->isLocale('ar') ? 'اقرأ قصتنا' : 'Read Our Story' }}
                </a>
                <a href="{{ route('home') }}#products"
                   class="inline-flex items-center gap-2 border border-white/25 px-8 py-3.5 font-condensed text-sm font-bold uppercase tracking-[0.18em] text-white transition-all duration-200 hover:bg-white/10">
                    {{ __('home.hero.cta') }}
                </a>
            </div>
        </div>
    </section>

@endsection
