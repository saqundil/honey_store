{{-- resources/views/pages/home.blade.php --}}
@extends('layouts.app')

@section('content')

    {{-- 1. Hero --}}
    <x-hero />

    <div class="relative overflow-hidden bg-[#fbf7f1]" id="homeStoryTheme">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.18),_transparent_44%),radial-gradient(circle_at_top_right,_rgba(199,72,23,0.12),_transparent_34%),linear-gradient(180deg,_rgba(255,255,255,0.94),_rgba(251,247,241,0.82)_52%,_rgba(251,247,241,1)_100%)]"></div>
        <div class="pointer-events-none absolute -left-16 top-24 h-48 w-48 rounded-full bg-[#f6c15a]/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-20 top-44 h-64 w-64 rounded-full bg-[#c74817]/10 blur-3xl"></div>

        {{-- 3. Mission + News --}}
        <section class="relative py-16 lg:py-24">
            <div class="mx-auto max-w-[1300px] px-6">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.08fr)_minmax(19rem,0.92fr)] lg:items-start">
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
        <section class="px-6 pb-20 pt-4 lg:pb-28">
            <div class="mx-auto max-w-[1180px]">
                <div class="relative overflow-hidden rounded-[2.4rem] border border-[#2c1b0b]/8 bg-[linear-gradient(135deg,#fff7ea_0%,#fff_42%,#fff5f2_100%)] p-8 shadow-[0_28px_70px_rgba(44,27,11,0.1)] sm:p-10 lg:p-12">
                    <div class="pointer-events-none absolute -left-10 top-0 h-36 w-36 rounded-full bg-[#f6c15a]/20 blur-3xl"></div>
                    <div class="pointer-events-none absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-[#c74817]/12 blur-3xl"></div>

                    <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(18rem,0.95fr)] lg:items-center">
                        <div>
                            <p class="home-eyebrow">{{ app()->isLocale('ar') ? 'من جبال قرغيزستان' : 'From the Mountains of Kyrgyzstan' }}</p>
                            <h2 class="mt-3 max-w-[14ch] font-condensed text-[clamp(2rem,4.2vw,3.5rem)] font-bold uppercase leading-[0.96] tracking-[-0.03em] text-[#2c1b0b] {{ app()->isLocale('ar') ? 'tracking-normal leading-[1.02]' : '' }}">
                                {{ app()->isLocale('ar') ? 'عسل نقي بتراث عريق' : 'Pure Honey. Rich Heritage.' }}
                            </h2>
                            <p class="mt-5 max-w-[60ch] text-[1rem] leading-8 text-honey-grey">
                                {{ __('home.hero.description') }}
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                <a href="{{ route('about') }}"
                                   class="inline-flex items-center justify-center gap-2 rounded-full bg-[#2c1b0b] px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-white shadow-[0_18px_42px_rgba(44,27,11,0.2)] transition duration-300 hover:-translate-y-0.5 hover:bg-[#1f1207]">
                                    <span>{{ app()->isLocale('ar') ? 'اقرأ قصتنا' : 'Read Our Story' }}</span>
                                </a>
                                <a href="{{ route('home') }}#products"
                                   class="inline-flex items-center justify-center rounded-full border border-[#2c1b0b]/10 bg-white px-6 py-3.5 font-condensed text-[0.84rem] font-bold uppercase tracking-[0.18em] text-[#2c1b0b] transition duration-300 hover:-translate-y-0.5 hover:border-[#c74817]/22 hover:text-[#c74817]">
                                    {{ __('home.hero.cta') }}
                                </a>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <article class="home-surface-soft p-5">
                                <p class="home-eyebrow text-[0.74rem]">{{ app()->isLocale('ar') ? 'الأصل' : 'Origin' }}</p>
                                <p class="mt-3 text-base font-semibold leading-7 text-[#2c1b0b]">{{ app()->isLocale('ar') ? 'مروج جبلية نقية تمنح العسل طابعه الزهري الواضح.' : 'Pristine mountain meadows that give the honey its clear floral character.' }}</p>
                            </article>
                            <article class="home-surface-soft p-5">
                                <p class="home-eyebrow text-[0.74rem]">{{ app()->isLocale('ar') ? 'العناية' : 'Care' }}</p>
                                <p class="mt-3 text-base font-semibold leading-7 text-[#2c1b0b]">{{ app()->isLocale('ar') ? 'اختيار واعٍ للمصدر وتقديم يحافظ على العسل كما خرج من الطبيعة.' : 'Intentional sourcing and careful handling that keep the honey close to nature.' }}</p>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

@endsection
