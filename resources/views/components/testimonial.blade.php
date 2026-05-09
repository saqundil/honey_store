{{-- resources/views/components/testimonial.blade.php --}}
@php
    $testimonialBackground = asset('images/Section_1.png');
    $galleryImages = trans('home.gallery.images');
@endphp

<section class="relative overflow-hidden bg-[#fbf7f1] py-20 lg:py-24">

    <img src="{{ $testimonialBackground }}"
         alt=""
         aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-[1] h-full w-full object-contain object-top select-none">

    {{-- Overlay --}}
    <div class="absolute inset-0 z-0 bg-[radial-gradient(circle_at_top_right,_rgba(199,72,23,0.08),_transparent_38%),linear-gradient(180deg,rgba(251,247,241,0.56),rgba(251,247,241,0.9))]"></div>

    <div class="relative z-[2] mx-auto max-w-[1300px] px-6">
        <div class="flex flex-col gap-14 lg:gap-16">
            <div class="home-surface-dark p-6 sm:p-8 lg:p-10">
                <div class="grid gap-8 md:grid-cols-[15rem_minmax(0,1fr)] md:items-center md:gap-10">

                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    <img src="{{ asset('images/home-1-testimonial-2.png') }}"
                         alt="{{ __('home.testimonial.avatar_alt') }}"
                         class="h-auto w-64 rounded-[1.7rem] object-cover shadow-[0_20px_44px_rgba(0,0,0,0.16)]">
                    {{-- Badge --}}
                    <div class="absolute -right-5 -top-5 flex h-16 w-16 items-center justify-center rounded-full bg-honey-gold shadow-[0_16px_32px_rgba(212,161,58,0.22)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                    </div>
                </div>

                {{-- Quote --}}
                <blockquote class="flex flex-col gap-5">
                    <span class="font-condensed text-[0.74rem] font-bold uppercase tracking-[0.24em] text-[#f6c15a]">{{ app()->isLocale('ar') ? 'صوت من مجتمعنا' : 'A voice from our community' }}</span>
                    <p class="text-lg italic leading-9 text-white/78 md:text-[1.75rem] md:leading-[2.6rem]">
                        "{{ __('home.testimonial.quote') }}"
                    </p>
                    <footer>
                        <p class="font-condensed text-xl font-bold uppercase text-white">
                            {{ __('home.testimonial.name') }}
                        </p>
                        <p class="font-condensed text-xs font-bold uppercase tracking-widest text-[#f6c15a]">
                            {{ __('home.testimonial.role') }}
                        </p>
                    </footer>
                </blockquote>
                </div>
            </div>

            <div class="home-surface px-6 py-8 sm:p-8 lg:p-10">
                <div class="mb-12 flex flex-col gap-4 text-center">
                    <span class="home-eyebrow mx-auto">{{ app()->isLocale('ar') ? 'اللحظات والصور' : 'Moments and imagery' }}</span>
                    <h2 class="home-title mx-auto max-w-[12ch]">
                        {{ __('home.gallery.heading') }}
                    </h2>
                    <p class="home-copy mx-auto max-w-2xl">
                        {{ __('home.gallery.description') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($galleryImages as $image)
                        <a href="{{ asset($image['src']) }}"
                           class="group home-card-hover block aspect-video overflow-hidden rounded-[1.45rem] border border-black/8 bg-white/70 shadow-[0_14px_36px_rgba(44,27,11,0.06)]">
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
