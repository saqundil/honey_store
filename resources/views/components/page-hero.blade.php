{{-- resources/views/components/page-hero.blade.php --}}
@props([
    'eyebrow' => '',
    'heading' => '',
    'summary' => '',
])

<section class="static-page-hero">
    <div class="static-page-hero__panel">
        <div class="static-page-hero__inner">
            <div>
                @if ($eyebrow)
                    <p class="static-page-hero__eyebrow">{{ $eyebrow }}</p>
                @endif
                <h1 class="static-page-hero__heading">{{ $heading }}</h1>
                @if ($summary)
                    <p class="static-page-hero__summary">{{ $summary }}</p>
                @endif
            </div>

            <div class="static-page-hero__brand-card">
                <span class="static-page-hero__brand-label">{{ __('home.brand.name') }}</span>
                <strong class="static-page-hero__brand-title">{{ __('home.hero.eyebrow') }}</strong>
                <p class="static-page-hero__brand-text">{{ $eyebrow ?: __('home.hero.cta') }}</p>
            </div>
        </div>
    </div>
</section>
