{{-- resources/views/pages/shipping.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.shipping.title').' | '.__('home.meta.title'))

@section('content')
    @php($phoneValue = __('home.footer.phone_value'))

    <div class="static-page-shell">
        <div class="static-page-container">
            <nav class="static-page-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">{{ __('pages.breadcrumb_home') }}</a>
                <span class="static-page-breadcrumb__divider {{ app()->isLocale('ar') ? 'rotate-180' : '' }}">/</span>
                <span class="text-honey-dark">{{ __('pages.shipping.title') }}</span>
            </nav>

            <x-page-hero
                :eyebrow="__('pages.shipping.hero_eyebrow')"
                :heading="__('pages.shipping.hero_heading')"
                :summary="__('pages.shipping.intro')" />

            <section class="mt-10 lg:mt-12">
                <div class="static-page-grid lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.88fr)]">
                    <div class="grid gap-4">
                        @foreach (__('pages.shipping.sections') as $index => $section)
                            <article class="static-page-card p-7 sm:p-8">
                                <div class="flex items-start gap-4 sm:gap-5">
                                    <span class="static-page-index-pill">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <div>
                                        <h2 class="font-condensed text-[1.25rem] font-bold uppercase leading-tight text-honey-dark sm:text-[1.38rem]">{{ $section['heading'] }}</h2>
                                        <p class="mt-3 text-base leading-8 text-honey-grey">{{ $section['text'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="grid gap-4 lg:sticky lg:top-28">
                        <div class="static-page-card p-6 sm:p-7">
                            <span class="static-page-kicker">{{ __('pages.shipping.title') }}</span>
                            <h3 class="static-page-title max-w-[11ch]">{{ __('pages.contact.info_heading') }}</h3>

                            <div class="static-page-divider-list mt-6">
                                <div>
                                    <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_phone_label') }}</p>
                                    <p class="mt-2 text-[0.98rem] leading-8 text-honey-grey">{{ $phoneValue }}</p>
                                </div>
                                <div>
                                    <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_email_label') }}</p>
                                    <p class="mt-2 text-[0.98rem] leading-8 text-honey-grey">{{ __('home.footer.email') }}</p>
                                </div>
                                <div>
                                    <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_hours_label') }}</p>
                                    <p class="mt-2 text-[0.98rem] leading-8 text-honey-grey">{{ __('pages.contact.info_hours_value') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="static-page-card-soft p-6 sm:p-7">
                            <span class="static-page-kicker">{{ __('home.nav.contact') }}</span>
                            <h3 class="mt-3 font-condensed text-[1.55rem] font-bold uppercase leading-tight text-honey-dark">{{ app()->isLocale('ar') ? 'تحتاج تفاصيل إضافية عن الشحن؟' : 'Need delivery details for your order?' }}</h3>
                            <p class="mt-4 text-sm leading-7 text-honey-grey">{{ app()->isLocale('ar') ? 'تواصل معنا إذا كنت تريد تقديرًا أوضح للمدة والتكلفة حسب وجهة الشحن.' : 'Reach out if you need a clearer estimate for timing and cost based on your shipping destination.' }}</p>
                            <a href="{{ route('contact') }}" class="static-page-cta mt-6 w-full sm:w-auto">{{ __('home.nav.contact') }}</a>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </div>

@endsection
