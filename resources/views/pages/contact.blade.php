{{-- resources/views/pages/contact.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.contact.title').' | '.__('home.meta.title'))

@section('content')
    @php($phoneValue = __('home.footer.phone_value'))
    @php($phoneHref = preg_replace('/[^\d+]/', '', $phoneValue))

    <div class="static-page-shell">
        <div class="static-page-container">
            <nav class="static-page-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">{{ __('pages.breadcrumb_home') }}</a>
                <span class="static-page-breadcrumb__divider {{ app()->isLocale('ar') ? 'rotate-180' : '' }}">/</span>
                <span class="text-honey-dark">{{ __('pages.contact.title') }}</span>
            </nav>

            <x-page-hero
                :eyebrow="__('pages.contact.hero_eyebrow')"
                :heading="__('pages.contact.hero_heading')"
                :summary="__('pages.contact.intro')" />

            <section class="mt-10 lg:mt-12">
                <div class="static-page-grid lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
                    <div class="static-page-card p-7 sm:p-9">

                        <h2 class="static-page-title max-w-[12ch]">{{ __('pages.contact.form_heading') }}</h2>

                        <form action="#" method="POST" class="mt-8 space-y-5">
                            @csrf
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="contact-name" class="figma-form-label">{{ __('pages.contact.form_name') }}</label>
                                    <input id="contact-name" name="name" type="text" required autocomplete="name" class="figma-form-input">
                                </div>
                                <div>
                                    <label for="contact-email" class="figma-form-label">{{ __('pages.contact.form_email') }}</label>
                                    <input id="contact-email" name="email" type="email" required autocomplete="email" class="figma-form-input">
                                </div>
                            </div>
                            <div>
                                <label for="contact-subject" class="figma-form-label">{{ __('pages.contact.form_subject') }}</label>
                                <input id="contact-subject" name="subject" type="text" required class="figma-form-input">
                            </div>
                            <div>
                                <label for="contact-message" class="figma-form-label">{{ __('pages.contact.form_message') }}</label>
                                <textarea id="contact-message" name="message" rows="5" required class="figma-form-textarea"></textarea>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <button type="submit" class="static-page-cta w-full sm:w-auto">{{ __('pages.contact.form_submit') }}</button>
                                <p class="text-sm leading-7 text-honey-muted">{{ __('pages.contact.form_disclaimer') }}</p>
                            </div>
                        </form>
                    </div>

                    <aside class="grid gap-5 lg:sticky lg:top-28">
                        <div class="static-page-card p-7 sm:p-8">
                            <span class="static-page-kicker">{{ __('pages.contact.title') }}</span>
                            <h3 class="static-page-title {{ app()->isLocale('ar') ? 'max-w-none whitespace-nowrap' : 'max-w-[11ch]' }}">{{ __('pages.contact.info_heading') }}</h3>

                            <ul class="static-page-divider-list mt-6" role="list">

                                <li class="flex items-start gap-4">
                                    <span class="static-page-icon">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.32.56 3.57.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.19 2.45.56 3.57a1 1 0 0 1-.24 1.02l-2.2 2.2Z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_phone_label') }}</p>
                                        <a href="tel:{{ $phoneHref }}" class="mt-2 block text-[0.98rem] leading-8 text-honey-grey transition-colors hover:text-honey-orange">{{ $phoneValue }}</a>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <span class="static-page-icon">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4.24-7.37 4.92a1 1 0 0 1-1.11 0L4 8.24V6l8 5.33L20 6v2.24Z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_email_label') }}</p>
                                        <a href="mailto:{{ __('home.footer.email') }}" class="mt-2 block text-[0.98rem] leading-8 text-honey-grey transition-colors hover:text-honey-orange">{{ __('home.footer.email') }}</a>
                                    </div>
                                </li>
                                <li class="flex items-start gap-4">
                                    <span class="static-page-icon">
                                        <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_hours_label') }}</p>
                                        <p class="mt-2 text-[0.98rem] leading-8 text-honey-grey">{{ __('pages.contact.info_hours_value') }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="static-page-card-soft p-6 sm:p-7">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_14px_30px_rgba(37,211,102,0.2)]">
                                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                                </span>
                                <div>
                                    <span class="static-page-kicker">WhatsApp</span>
                                    <h3 class="mt-3 font-condensed text-[1.55rem] font-bold uppercase leading-tight text-[#1b7f46]">{{ __('home.product_page.whatsapp_button') }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-honey-grey">{{ __('home.product_page.whatsapp_hint') }}</p>
                                </div>
                            </div>

                            <a href="https://api.whatsapp.com/send?phone={{ __('home.product_page.whatsapp_phone') }}" target="_blank" rel="noopener" class="static-page-cta mt-6 w-full sm:w-auto">
                                {{ __('home.product_page.whatsapp_button') }}
                            </a>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </div>

@endsection
