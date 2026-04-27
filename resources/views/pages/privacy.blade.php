{{-- resources/views/pages/privacy.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.privacy.title').' | '.__('home.meta.title'))

@section('content')

    <x-page-hero :eyebrow="__('pages.privacy.hero_eyebrow')" :heading="__('pages.privacy.hero_heading')" />

    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-[1130px] px-6 pt-10">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} inline-block">/</span>
            <span class="text-honey-dark">{{ __('pages.privacy.title') }}</span>
        </nav>
    </div>

    <section class="mx-auto max-w-[850px] px-6 py-16 lg:py-20">
        <p class="mb-10 text-sm uppercase tracking-widest text-honey-muted">{{ __('pages.privacy.last_updated') }}</p>

        <div class="space-y-10">
            @foreach (__('pages.privacy.sections') as $section)
                <article>
                    <h2 class="font-condensed text-2xl font-bold uppercase text-honey-dark">{{ $section['heading'] }}</h2>
                    <p class="mt-4 text-base leading-8 text-honey-grey">{{ $section['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

@endsection
