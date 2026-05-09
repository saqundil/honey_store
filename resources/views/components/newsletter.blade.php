{{-- resources/views/components/newsletter.blade.php --}}
@php
    $newsletterBackground = asset('images/Section_2.png');
    $types = trans('home.types.items');
@endphp

<section class="relative overflow-hidden bg-[#fbf7f1] py-16 lg:py-24">

    <img src="{{ $newsletterBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center opacity-30 select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,_rgba(246,193,90,0.12),_transparent_38%),linear-gradient(180deg,rgba(251,247,241,0.64),rgba(251,247,241,0.96))]"></div>

    <div class="relative mx-auto max-w-[1300px] px-6">
        <div class="flex w-full flex-col gap-16 lg:gap-20">
            <div class="home-surface grid w-full gap-10 px-6 py-8 sm:p-8 lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:items-center lg:gap-12 lg:p-10">

                {{-- Left Decorative Image --}}
                <div class="hidden lg:block">
                    <img src="{{ asset('images/h1-img-14.png') }}"
                         alt="{{ __('home.newsletter.image_alt') }}"
                         class="w-full max-w-[590px] rounded-[1.8rem] border border-[#2c1b0b]/8 bg-[#f4ede5] object-cover shadow-[0_18px_42px_rgba(44,27,11,0.08)]">
                </div>

                {{-- Right: Form Content --}}
                <div class="flex w-full flex-col gap-6">
                    <span class="home-eyebrow">{{ app()->isLocale('ar') ? 'ابقَ قريبًا' : 'Stay close' }}</span>
                    <h2 class="home-title max-w-[12ch]">
                        {{ __('home.newsletter.heading') }}
                    </h2>
                    <p class="home-copy max-w-[34rem]">
                        {{ __('home.newsletter.description') }}
                    </p>

                    <form action="{{ url('/newsletter/subscribe') }}" method="POST" class="mt-2 flex flex-col gap-3 sm:flex-row">
                        @csrf
                        <label for="newsletter-email" class="sr-only">{{ __('home.newsletter.email_label') }}</label>
                        <input id="newsletter-email"
                               type="email"
                               name="email"
                               placeholder="{{ __('home.newsletter.email_placeholder') }}"
                               required
                               class="flex-1 rounded-full border border-[#2c1b0b]/10 bg-white px-5 py-4 text-base text-honey-grey placeholder:text-honey-grey/70 outline-none transition-colors focus:border-honey-orange focus:ring-4 focus:ring-honey-orange/10">
                        <button type="submit"
                                class="rounded-full bg-honey-orange px-10 py-4 font-condensed font-bold text-sm uppercase tracking-widest text-white transition-opacity hover:opacity-90 whitespace-nowrap">
                            {{ __('home.newsletter.submit') }}
                        </button>
                    </form>
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
