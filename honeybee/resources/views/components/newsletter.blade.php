{{-- resources/views/components/newsletter.blade.php --}}
@php
    $newsletterBackground = asset('images/Section_2.png');
    $types = trans('home.types.items');
@endphp

<section class="relative overflow-hidden bg-honey-cream py-16 lg:py-24">

    <img src="{{ $newsletterBackground }}"
         alt=""
         aria-hidden="true"
         class="pointer-events-none absolute inset-0 h-full w-full object-contain object-center select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-honey-cream/36"></div>

    <div class="relative mx-auto max-w-[1300px] px-6">
        <div class="flex w-full flex-col gap-16 lg:gap-20">
            <div class="flex w-full flex-col items-center gap-12 lg:flex-row">

                {{-- Left Decorative Image --}}
                <div class="hidden lg:block lg:w-1/2 flex-shrink-0">
                    <img src="{{ asset('images/h1-img-14.png') }}"
                         alt="{{ __('home.newsletter.image_alt') }}"
                         class="w-full max-w-[590px] h-auto object-cover">
                </div>

                {{-- Right: Form Content --}}
                <div class="w-full lg:w-1/2 flex flex-col gap-6">
                    <h2 class="font-condensed font-bold text-4xl md:text-5xl uppercase text-honey-dark leading-tight">
                        {{ __('home.newsletter.heading') }}
                    </h2>
                    <p class="text-honey-grey text-base md:text-lg leading-relaxed">
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
                               class="flex-1 px-5 py-4 border border-[#545050] bg-transparent text-honey-grey text-base placeholder:text-honey-grey/70 outline-none focus:border-honey-orange transition-colors">
                        <button type="submit"
                                class="px-10 py-4 bg-honey-orange font-condensed font-bold text-sm uppercase tracking-widest text-white hover:opacity-90 transition-opacity whitespace-nowrap">
                            {{ __('home.newsletter.submit') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-t border-black/10 pt-12 lg:pt-14">
                <div class="mb-12 flex flex-col gap-4 text-center lg:mb-14">
                    <h2 class="font-condensed font-bold text-4xl uppercase leading-tight text-honey-dark md:text-5xl">
                        {{ __('home.types.heading') }}
                    </h2>
                    <p class="mx-auto max-w-xl text-base leading-relaxed text-honey-grey md:text-lg">
                        {{ __('home.types.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @foreach ($types as $type)
                        <article class="group flex items-center gap-6 border border-black/8 bg-honey-card/95 px-8 py-7 shadow-[0_16px_40px_rgba(44,27,11,0.06)] backdrop-blur-sm transition-shadow hover:shadow-[0_20px_46px_rgba(44,27,11,0.1)]">
                            <div class="flex-shrink-0">
                                <img src="{{ asset($type['image']) }}"
                                     alt="{{ $type['name'] }}"
                                     class="h-auto w-28 object-contain">
                            </div>

                            <div class="flex flex-col gap-2">
                                <h3 class="font-condensed text-2xl font-bold uppercase leading-tight text-honey-dark">
                                    {{ $type['name'] }}
                                </h3>
                                <p class="text-sm leading-relaxed text-honey-muted">
                                    {{ $type['excerpt'] }}
                                </p>
                                <a href="#"
                                   class="inline-flex items-center gap-2 font-condensed text-xs font-bold uppercase tracking-widest text-honey-orange transition-colors hover:text-honey-orange/80">
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
