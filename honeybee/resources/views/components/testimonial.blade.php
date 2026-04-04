{{-- resources/views/components/testimonial.blade.php --}}
@php
    $testimonialBackground = asset('images/Section_1.png');
    $galleryImages = trans('home.gallery.images');
@endphp

<section class="relative flex items-center overflow-hidden bg-honey-cream py-20 md:min-h-[34rem] lg:min-h-[42rem] lg:py-28">

    <img src="{{ $testimonialBackground }}"
         alt=""
         aria-hidden="true"
            class="pointer-events-none absolute inset-0 h-full w-full object-contain object-top select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-honey-cream/34"></div>

    <div class="relative mx-auto max-w-[1300px] px-6">
        <div class="flex flex-col gap-14 lg:gap-16">
            <div class="flex flex-col items-center gap-8 md:flex-row md:gap-12">

                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    <img src="{{ asset('images/home-1-testimonial-2.png') }}"
                         alt="{{ __('home.testimonial.avatar_alt') }}"
                         class="h-auto w-64 rounded-xl object-cover">
                    {{-- Badge --}}
                    <div class="absolute -right-5 -top-5 flex h-16 w-16 items-center justify-center rounded-full bg-honey-gold shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                </div>

                {{-- Quote --}}
                <blockquote class="flex flex-col gap-5">
                    <p class="text-lg italic leading-relaxed text-honey-muted md:text-2xl">
                        "{{ __('home.testimonial.quote') }}"
                    </p>
                    <footer>
                        <p class="font-condensed text-xl font-bold uppercase text-honey-dark">
                            {{ __('home.testimonial.name') }}
                        </p>
                        <p class="font-condensed text-xs font-bold uppercase tracking-widest text-honey-orange">
                            {{ __('home.testimonial.role') }}
                        </p>
                    </footer>
                </blockquote>
            </div>

            <div class=" pt-12 lg:pt-14">
                <div class="mb-12 flex flex-col gap-4 text-center">
                    <h2 class="font-condensed text-4xl font-bold uppercase leading-tight text-honey-dark md:text-5xl">
                        {{ __('home.gallery.heading') }}
                    </h2>
                    <p class="mx-auto max-w-2xl text-base leading-relaxed text-honey-grey md:text-lg">
                        {{ __('home.gallery.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($galleryImages as $image)
                        <a href="{{ asset($image['src']) }}"
                           class="group block aspect-video overflow-hidden rounded-[1.25rem] border border-black/8 bg-white/60 shadow-[0_14px_36px_rgba(44,27,11,0.06)]">
                            <img src="{{ asset($image['src']) }}"
                                 alt="{{ $image['alt'] }}"
                                 loading="lazy"
                                 decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
