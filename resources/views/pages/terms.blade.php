{{-- resources/views/pages/terms.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.terms.title').' | '.__('home.meta.title'))

@section('content')
    <div class="static-page-shell">
        <div class="static-page-container">
            <nav class="static-page-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">{{ __('pages.breadcrumb_home') }}</a>
                <span class="static-page-breadcrumb__divider {{ app()->isLocale('ar') ? 'rotate-180' : '' }}">/</span>
                <span class="text-honey-dark">{{ __('pages.terms.title') }}</span>
            </nav>

            <x-page-hero :eyebrow="__('pages.terms.hero_eyebrow')" :heading="__('pages.terms.hero_heading')" />

            <section class="mt-10 lg:mt-12">
                <div class="static-page-card p-6 sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <span class="static-page-meta">{{ __('pages.terms.last_updated') }}</span>
                        <a href="{{ route('contact') }}" class="static-page-cta w-full sm:w-auto">{{ __('home.nav.contact') }}</a>
                    </div>

                    <div class="static-page-legal-list mt-8">
                        @foreach (__('pages.terms.sections') as $section)
                            <article class="static-page-legal-item">
                                <h2>{{ $section['heading'] }}</h2>
                                <p>{{ $section['text'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

@endsection
