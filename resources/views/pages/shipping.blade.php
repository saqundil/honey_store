{{-- resources/views/pages/shipping.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.shipping.title').' | '.__('home.meta.title'))

@section('content')

    <x-page-hero :eyebrow="__('pages.shipping.hero_eyebrow')" :heading="__('pages.shipping.hero_heading')" />

    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-[1130px] px-6 pt-10">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} inline-block">/</span>
            <span class="text-honey-dark">{{ __('pages.shipping.title') }}</span>
        </nav>
    </div>

    <section class="mx-auto max-w-[850px] px-6 py-16 lg:py-20">
        <p class="mb-12 text-center text-base leading-8 text-honey-grey md:text-lg">
            {{ __('pages.shipping.intro') }}
        </p>

        <div class="space-y-8">
            @foreach (__('pages.shipping.sections') as $index => $section)
                <article class="rounded-[1.75rem] border border-black/5 bg-white p-7 shadow-[0_12px_32px_rgba(44,27,11,0.05)] sm:p-8">
                    <div class="flex items-start gap-5">
                        <span class="mt-1 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-honey-gold/15 font-condensed text-lg font-bold text-honey-gold">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <h2 class="font-condensed text-xl font-bold uppercase text-honey-dark">{{ $section['heading'] }}</h2>
                            <p class="mt-3 text-base leading-8 text-honey-grey">{{ $section['text'] }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

@endsection
