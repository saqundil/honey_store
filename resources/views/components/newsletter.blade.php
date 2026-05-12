{{-- resources/views/components/newsletter.blade.php --}}
@php
    $newsletterBackground = asset('images/Section_2.png');
    $types = trans('home.types.items');
    $tradeHighlights = collect(__('home.newsletter.highlights'));
    $tradeWhatsAppMessage = rawurlencode(__('home.newsletter.whatsapp_message'));
@endphp

<section class="relative isolate overflow-hidden bg-[#fbf7f1] py-16 lg:py-24">

    <img src="{{ $newsletterBackground }}"
         alt=""
         aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-[1] h-full w-full object-contain object-center opacity-30 select-none">

    {{-- Overlay --}}
        <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_bottom_left,_rgba(246,193,90,0.16),_transparent_38%),radial-gradient(circle_at_top_right,_rgba(199,72,23,0.08),_transparent_34%),linear-gradient(180deg,#fbf7f1_0%,#fbf7f1_8%,rgba(255,244,224,0.95)_22%,rgba(255,238,210,0.88)_44%,rgba(251,247,241,0.96)_100%)]"></div>

        <div class="relative z-[2] mx-auto max-w-[1300px] px-6">
        <div class="flex w-full flex-col gap-16 lg:gap-20">
            <div class="home-surface grid w-full gap-10 px-6 py-8 sm:p-8 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:items-center lg:gap-12 lg:p-10">

                {{-- Left Decorative Image --}}
                <div class="hidden lg:block">
                    <img src="{{ asset('images/h1-img-14.png') }}"
                         alt="{{ __('home.newsletter.image_alt') }}"
                         class="w-full max-w-[590px] rounded-[1.8rem] border border-[#2c1b0b]/8 bg-[#f4ede5] object-cover shadow-[0_18px_42px_rgba(44,27,11,0.08)]">
                </div>

                {{-- Right: Wholesale / Supply Content --}}
                <div class="flex w-full flex-col gap-6">
                    <span class="home-eyebrow">{{ __('home.newsletter.eyebrow') }}</span>
                    <h2 class="home-title {{ app()->isLocale('ar') ? 'max-w-none whitespace-nowrap' : 'max-w-[12ch]' }}">
                        {{ __('home.newsletter.heading') }}
                    </h2>
                    <p class="home-copy max-w-[34rem]">
                        {{ __('home.newsletter.description') }}
                    </p>

                    @if ($tradeHighlights->isNotEmpty())
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($tradeHighlights as $highlight)
                                <article class="home-surface-soft p-4 sm:p-5">
                                    <p class="font-condensed text-[1.02rem] font-bold uppercase leading-tight text-honey-dark">
                                        {{ $highlight['title'] }}
                                    </p>
                                    <p class="mt-2 text-sm leading-7 text-honey-muted">
                                        {{ $highlight['excerpt'] }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="https://api.whatsapp.com/send?phone={{ __('home.product_page.whatsapp_phone') }}&text={{ $tradeWhatsAppMessage }}"
                           target="_blank"
                           rel="noopener"
                           class="inline-flex items-center justify-center rounded-full bg-honey-orange px-8 py-4 font-condensed text-sm font-bold uppercase tracking-widest text-white transition-opacity hover:opacity-90">
                            {{ __('home.newsletter.primary_cta') }}
                        </a>
                        <a href="{{ route('contact') }}"
                           class="inline-flex items-center justify-center rounded-full border border-[#2c1b0b]/10 bg-white px-8 py-4 font-condensed text-sm font-bold uppercase tracking-widest text-[#2c1b0b] transition duration-300 hover:-translate-y-0.5 hover:border-[#c74817]/22 hover:text-[#c74817]">
                            {{ __('home.newsletter.secondary_cta') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="home-surface px-6 py-8 sm:p-8 lg:p-10">
                <div class="mb-12 flex flex-col gap-4 text-center lg:mb-14">
                    <span class="home-eyebrow mx-auto">{{ app()->isLocale('ar') ? 'أنواع العسل' : 'Honey varieties' }}</span>
                    <h2 class="home-title mx-auto max-w-[12ch]">
                        {{ __('home.types.heading') }}
                    </h2>
                    <p class="home-copy mx-auto max-w-xl">
                        {{ __('home.types.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach ($types as $type)
                        <article class="home-surface-soft home-card-hover group flex items-center gap-6 px-8 py-7">
                            <div class="flex-shrink-0">
                                <img src="{{ asset($type['image']) }}"
                                     alt="{{ $type['name'] }}"
                                     class="h-auto w-28 object-contain">
                            </div>

                            <div class="flex flex-col gap-2">
                                <h3 class="font-condensed text-2xl font-bold uppercase leading-tight text-honey-dark">
                                    {{ $type['name'] }}
                                </h3>
                                <p class="text-sm leading-7 text-honey-muted">
                                    {{ $type['excerpt'] }}
                                </p>
                                <a href="#" class="home-link mt-1 w-fit text-honey-orange hover:text-honey-orange/80">
                                    {{ __('home.types.learn_more') }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</section>
