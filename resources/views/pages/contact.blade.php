{{-- resources/views/pages/contact.blade.php --}}
@extends('layouts.app')

@section('title', __('pages.contact.title').' | '.__('home.meta.title'))

@section('content')

    <x-page-hero :eyebrow="__('pages.contact.hero_eyebrow')" :heading="__('pages.contact.hero_heading')" />

    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-[1130px] px-6 pt-10">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-honey-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="transition-colors hover:text-honey-orange">{{ __('pages.breadcrumb_home') }}</a>
            <span class="{{ app()->isLocale('ar') ? 'rotate-180' : '' }} inline-block">/</span>
            <span class="text-honey-dark">{{ __('pages.contact.title') }}</span>
        </nav>
    </div>

    <section class="mx-auto max-w-[1130px] px-6 py-16 lg:py-20">
        <p class="mx-auto mb-14 max-w-2xl text-center text-base leading-8 text-honey-grey md:text-lg">
            {{ __('pages.contact.intro') }}
        </p>

        <div class="grid gap-12 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16">

            {{-- Contact Form --}}
            <div class="rounded-[2rem] border border-black/5 bg-white p-7 shadow-[0_24px_60px_rgba(44,27,11,0.08)] sm:p-9">
                <h2 class="font-condensed text-2xl font-bold uppercase text-honey-dark md:text-3xl">
                    {{ __('pages.contact.form_heading') }}
                </h2>

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
                        <button type="submit" class="figma-submit-button">{{ __('pages.contact.form_submit') }}</button>
                        <p class="text-sm text-honey-muted">{{ __('pages.contact.form_disclaimer') }}</p>
                    </div>
                </form>
            </div>

            {{-- Contact Information --}}
            <aside class="space-y-8">
                <div class="rounded-[1.75rem] border border-black/5 bg-white p-7 shadow-[0_16px_40px_rgba(44,27,11,0.06)]">
                    <h3 class="font-condensed text-xl font-bold uppercase text-honey-dark">{{ __('pages.contact.info_heading') }}</h3>

                    <ul class="mt-6 space-y-5 text-[15px] text-honey-grey" role="list">
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-honey-orange/10 text-honey-orange">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M12 2 3 9v11h6v-6h6v6h6V9l-9-7Zm0 2.46 7 5.45V18h-2v-6H7v6H5V9.91l7-5.45Z"/></svg>
                            </span>
                            <div>
                                <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_address_label') }}</p>
                                <p class="mt-1 leading-7">{{ __('home.footer.address_line_1') }}, {{ __('home.footer.address_line_2') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-honey-orange/10 text-honey-orange">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.32.56 3.57.56a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.3 21 3 13.7 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.19 2.45.56 3.57a1 1 0 0 1-.24 1.02l-2.2 2.2Z"/></svg>
                            </span>
                            <div>
                                <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_phone_label') }}</p>
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', __('home.footer.phone_value')) }}" class="mt-1 block leading-7 transition-colors hover:text-honey-orange">{{ __('home.footer.phone_value') }}</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-honey-orange/10 text-honey-orange">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4.24-7.37 4.92a1 1 0 0 1-1.11 0L4 8.24V6l8 5.33L20 6v2.24Z"/></svg>
                            </span>
                            <div>
                                <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_email_label') }}</p>
                                <a href="mailto:{{ __('home.footer.email') }}" class="mt-1 block leading-7 transition-colors hover:text-honey-orange">{{ __('home.footer.email') }}</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-honey-orange/10 text-honey-orange">
                                <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7Z"/></svg>
                            </span>
                            <div>
                                <p class="font-condensed text-xs font-bold uppercase tracking-[0.18em] text-honey-dark">{{ __('pages.contact.info_hours_label') }}</p>
                                <p class="mt-1 leading-7">{{ __('pages.contact.info_hours_value') }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                {{-- WhatsApp CTA --}}
                <a href="https://api.whatsapp.com/send?phone={{ __('home.product_page.whatsapp_phone') }}" target="_blank" rel="noopener"
                   class="flex items-center gap-4 rounded-[1.75rem] border border-[#25D366]/20 bg-[#25D366]/8 p-6 transition duration-300 hover:bg-[#25D366]/14 hover:shadow-[0_12px_30px_rgba(37,211,102,0.1)]">
                    <span class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    </span>
                    <div>
                        <p class="font-condensed text-lg font-bold uppercase text-[#1b7f46]">{{ __('home.product_page.whatsapp_button') }}</p>
                        <p class="mt-1 text-sm text-honey-muted">{{ __('home.product_page.whatsapp_hint') }}</p>
                    </div>
                </a>
            </aside>
        </div>
    </section>

@endsection
