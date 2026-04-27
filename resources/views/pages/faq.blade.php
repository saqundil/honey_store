{{-- resources/views/pages/faq.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.faq.title').' | '.__('home.meta.title'))

@section('content')

    <x-page-hero :eyebrow="__('pages.faq.hero_eyebrow')" :heading="__('pages.faq.hero_heading')" />

    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-[1130px] px-6 pt-10">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} inline-block">/</span>
            <span class="text-honey-dark">{{ __('pages.faq.title') }}</span>
        </nav>
    </div>

    <section class="mx-auto max-w-[850px] px-6 py-16 lg:py-20">
        <div class="space-y-4" id="faq-accordion">
            @foreach (__('pages.faq.items') as $index => $item)
                <details class="group rounded-[1.5rem] border border-black/5 bg-white shadow-[0_10px_30px_rgba(44,27,11,0.04)] transition-shadow hover:shadow-[0_14px_36px_rgba(44,27,11,0.07)]" {{ $index === 0 ? 'open' : '' }}>
                    <summary class="flex cursor-pointer items-center justify-between gap-4 px-6 py-5 font-condensed text-lg font-bold uppercase text-honey-dark transition-colors hover:text-honey-orange sm:px-8 sm:py-6 [&::-webkit-details-marker]:hidden">
                        <span>{{ $item['question'] }}</span>
                        <span class="flex-shrink-0 text-honey-orange transition-transform duration-300 group-open:rotate-45">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                        </span>
                    </summary>
                    <div class="faq-answer border-t border-black/5 px-6 pb-6 pt-4 sm:px-8 sm:pb-7 sm:pt-5">
                        <p class="text-base leading-8 text-honey-grey">{{ $item['answer'] }}</p>
                    </div>
                </details>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="mt-14 rounded-[2rem] border border-black/5 bg-[#fffaf5] p-8 text-center shadow-[0_16px_40px_rgba(44,27,11,0.05)] sm:p-10">
            <h3 class="font-condensed text-2xl font-bold uppercase text-honey-dark">
                {{ app()->isLocale('ar') ? 'لم تجد إجابتك؟' : 'Still have questions?' }}
            </h3>
            <p class="mt-3 text-base leading-7 text-honey-grey">
                {{ app()->isLocale('ar') ? 'تواصل معنا وسنكون سعداء بمساعدتك.' : 'Get in touch and we\'ll be happy to help.' }}
            </p>
            <a href="{{ route('contact') }}"
               class="mt-6 inline-flex items-center gap-2 bg-honey-gold px-8 py-3.5 font-condensed text-sm font-bold uppercase tracking-[0.18em] text-white transition-all duration-200 hover:opacity-90">
                {{ __('home.nav.contact') }}
            </a>
        </div>
    </section>

@endsection
