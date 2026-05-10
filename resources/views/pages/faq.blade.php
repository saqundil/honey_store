{{-- resources/views/pages/faq.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.faq.title').' | '.__('home.meta.title'))

@section('content')
    <div class="static-page-shell">
        <div class="static-page-container">
            <nav class="static-page-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">{{ __('pages.breadcrumb_home') }}</a>
                <span class="static-page-breadcrumb__divider {{ app()->isLocale('ar') ? 'rotate-180' : '' }}">/</span>
                <span class="text-honey-dark">{{ __('pages.faq.title') }}</span>
            </nav>

            <x-page-hero :eyebrow="__('pages.faq.hero_eyebrow')" :heading="__('pages.faq.hero_heading')" />

            <section class="mt-10 lg:mt-12">
                <div class="static-page-card p-4 sm:p-5 lg:p-6">
                    <div class="space-y-4" id="faq-accordion">
                        @foreach (__('pages.faq.items') as $index => $item)
                            <details class="group static-page-card-soft px-5 py-4 sm:px-6 sm:py-5" {{ $index === 0 ? 'open' : '' }}>
                                <summary class="flex cursor-pointer items-start justify-between gap-4 [&::-webkit-details-marker]:hidden">
                                    <div class="flex items-start gap-4">
                                        <span class="static-page-index-pill">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="pt-1 font-condensed text-[1.02rem] font-bold uppercase leading-7 text-honey-dark transition-colors group-hover:text-honey-orange sm:text-[1.08rem]">{{ $item['question'] }}</span>
                                    </div>
                                    <span class="mt-2 flex-shrink-0 text-honey-orange transition-transform duration-300 group-open:rotate-45">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </span>
                                </summary>
                                <div class="pt-4">
                                    <p class="static-page-copy">{{ $item['answer'] }}</p>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>

                <div class="static-page-card mt-8 p-8 text-center sm:p-10">
                    <span class="static-page-kicker mx-auto">{{ __('pages.faq.title') }}</span>
                    <h3 class="static-page-title mx-auto max-w-[10ch]">{{ app()->isLocale('ar') ? 'لم تجد إجابتك؟' : 'Still have questions?' }}</h3>
                    <p class="mx-auto mt-4 max-w-[34rem] text-base leading-8 text-honey-grey">{{ app()->isLocale('ar') ? 'تواصل معنا وسنكون سعداء بمساعدتك.' : 'Get in touch and we\'ll be happy to help.' }}</p>
                    <a href="{{ route('contact') }}" class="static-page-cta mt-6">{{ __('home.nav.contact') }}</a>
                </div>
            </section>
        </div>
    </div>

@endsection
