{{-- resources/views/components/hero.blade.php --}}
@php
     $isArabic = app()->isLocale('ar');
@endphp

<section class="relative overflow-hidden bg-[#fbf7f1]" data-hero-scene>
     <div class="mx-auto hidden w-full max-w-[1920px] lg:block">
          <div class="relative h-[calc(100vh_-_80px)] min-h-[540px] max-h-[760px] overflow-hidden lg:h-[calc(100vh_-_96px)] lg:min-h-[580px] xl:max-h-[820px]">
               <svg viewBox="0 0 1920 980"
                     aria-hidden="true"
                     class="pointer-events-none absolute inset-0 h-full w-full opacity-0">
                    <path id="hero-bee-path-down" data-hero-bee-path-down d="M482 182 C 650 260 340 390 520 520 S 680 770 410 870" />
                    <path id="hero-bee-path-up" data-hero-bee-path-up d="M410 870 C 250 720 560 610 365 430 S 210 180 455 120" />
               </svg>

               <img src="{{ asset('images/h1-rev-img-1.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute left-0 top-[14%] w-[7.9%] select-none xl:w-[8.4%]">

               <img src="{{ asset('images/h1-rev-img-8.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute left-0 top-[70.5%] w-[7.1%] select-none xl:w-[7.7%]">

               <img src="{{ asset('images/h1-rev-img-7.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute left-[91.1%] top-[49%] w-[7.8%] select-none xl:left-[90.8%] xl:w-[8.5%]">

                  <img src="{{ asset('images/h1-rev-img-11.png') }}"
                       alt=""
                       aria-hidden="true"
                       class="pointer-events-none absolute left-[26.1%] top-[76.1%] w-[24.5833%] select-none">

               <div class="absolute left-[16.1458%] top-[24.5%] max-w-[27rem] xl:max-w-[29rem]">
                    <p class="font-condensed text-lg font-bold uppercase tracking-[0.3em] text-honey-dark">
                         {{ __('home.hero.eyebrow') }}
                    </p>

                         @if ($isArabic)
                              <h1 class="mt-6 font-condensed font-bold uppercase text-honey-dark" style="font-size: clamp(3.55rem, 5.2vw, 5.85rem); line-height: 1; letter-spacing: 0;">
                                   {{ __('home.hero.title') }}
                              </h1>
                         @else
                              <h1 class="mt-6 font-condensed font-bold uppercase text-honey-dark" style="font-size: clamp(4.25rem, 6.2vw, 7rem); line-height: 0.83; letter-spacing: 0.01em;">
                                   {{ __('home.hero.title') }}
                              </h1>
                         @endif

                    <p class="mt-8 max-w-[28rem] text-base leading-[1.8] text-honey-grey xl:max-w-[30rem] xl:text-[17px] xl:leading-[1.75]">
                         {{ __('home.hero.description') }}
                    </p>

                    <a href="{{ route('home') }}#products" class="hero-button mt-10 px-8 py-3.5 xl:mt-12 xl:px-[2.3125rem] xl:py-[0.9375rem]">
                         {{ __('home.hero.cta') }}
                    </a>
               </div>

               <img src="{{ asset('images/h1-rev-img-12.png') }}"
                     alt="{{ __('home.hero.product_alt') }}"
                       class="pointer-events-none absolute left-[52.25%] top-[12%] w-[31.5%] select-none xl:left-[51.1%] xl:top-[10.5%] xl:w-[33.5%]">

                  <img src="{{ asset('images/h1-rev-img-10.png') }}"
                       alt=""
                       aria-hidden="true"
                     class="pointer-events-none absolute left-[76.4063%] top-[20.5%] w-[23.75%] select-none opacity-25 [filter:brightness(1.4)_contrast(0.78)]">

               <img src="{{ asset('images/bee.png') }}"
                     alt=""
                     aria-hidden="true"
                     class="hero-bee hero-bee--float-medium pointer-events-none absolute left-[4.6875%] top-[58.5%] w-[2.96875%] select-none">

               <div data-hero-bee-swoop
                     class="pointer-events-none absolute left-0 top-0 w-[3.85417%] select-none">
                    <img src="{{ asset('images/h1-rev-img-2.png') }}"
                          alt=""
                          aria-hidden="true"
                          data-hero-bee-sprite
                          class="block w-full">
               </div>

               <img src="{{ asset('images/h1-rev-img-3.png') }}"
                     alt=""
                     aria-hidden="true"
                     class="hero-bee hero-bee--float-fast pointer-events-none absolute left-[53.2813%] top-[22%] w-[3.02083%] select-none">

               <img src="{{ asset('images/h1-rev-img-4.png') }}"
                     alt=""
                     aria-hidden="true"
                     class="hero-bee hero-bee--float-medium pointer-events-none absolute left-[79.0104%] top-[12.5%] w-[4.01042%] select-none">

               <img src="{{ asset('images/h1-rev-img-5.png') }}"
                     alt=""
                     aria-hidden="true"
                     class="hero-bee hero-bee--float-fast pointer-events-none absolute left-[96.875%] top-[13.5%] w-[2.1875%] select-none">

               <img src="{{ asset('images/h1-rev-img-9.png') }}"
                       alt="{{ __('home.hero.badge_alt') }}"
                     class="pointer-events-none absolute left-[79.8%] top-[29.5%] w-[10.5%] select-none xl:w-[11%]">
          </div>
     </div>

          <div class="w-full lg:hidden">
              <div class="relative h-[calc(100dvh_-_80px)] min-h-[calc(100vh_-_80px)] overflow-hidden border-t border-black/5 bg-[#fbf7f1]">
               <svg viewBox="0 0 390 760"
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0 h-full w-full opacity-0">
                    <path data-hero-bee-path-down d="M286 830 C 320 700 302 540 244 398 S 222 270 196 208 S 68 104 -34 -52" />
                    <path data-hero-bee-path-up d="M-34 -52 C 96 34 252 78 314 188 S 322 434 278 566 S 236 740 286 830" />
               </svg>

               <img src="{{ asset('images/h1-rev-img-1.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute -left-4 top-[7.5%] w-[5.75rem] select-none opacity-95 sm:-left-3 sm:w-[6.5rem]">

               <img src="{{ asset('images/h1-rev-img-8.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute -left-5 bottom-[3%] w-[6.75rem] select-none opacity-95 sm:w-[7.5rem]">

               <img src="{{ asset('images/h1-rev-img-7.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute -right-4 bottom-[4%] w-[5.75rem] select-none opacity-95 sm:-right-3 sm:w-[6.5rem]">



               <img src="{{ asset('images/h1-rev-img-3.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="hero-bee hero-bee--float-fast pointer-events-none absolute left-[14%] top-[14%] w-10 select-none sm:left-[15%] sm:w-12">

               <img src="{{ asset('images/h1-rev-img-4.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="hero-bee hero-bee--float-medium pointer-events-none absolute right-[10%] top-[11%] w-14 select-none sm:right-[11%] sm:w-16">

               <img src="{{ asset('images/h1-rev-img-5.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="pointer-events-none absolute right-[6%] top-[8%] w-8 select-none sm:right-[7%] sm:w-10">

               <img src="{{ asset('images/bee.png') }}"
                     alt=""
                     aria-hidden="true"
                       class="hero-bee hero-bee--float-medium pointer-events-none absolute left-[12%] bottom-[10%] w-10 select-none sm:left-[13%] sm:w-12">

                      <div class="relative z-10 flex h-full flex-col items-center justify-center px-5 pb-[12vh] pt-[6vh] text-center">
                           <img src="{{ asset('images/h1-rev-img-12.png') }}"
                                 alt="{{ __('home.hero.product_alt') }}"
                                 class="mb-6 w-full max-w-[13.5rem] select-none sm:max-w-[15rem]">

                                                                  @if ($isArabic)
                                                                      <h1 class="max-w-[10ch] font-condensed font-bold uppercase text-honey-dark" style="font-size: clamp(2.95rem, 11.5vw, 4.15rem); line-height: 1; letter-spacing: 0;">
                         {{ __('home.hero.title') }}
                    </h1>
                                                                  @else
                                                                      <h1 class="max-w-[10ch] font-condensed font-bold uppercase text-honey-dark" style="font-size: clamp(3.25rem, 16vw, 5.4rem); line-height: 0.82; letter-spacing: 0.01em;">
                         {{ __('home.hero.title') }}
                    </h1>
                                                                  @endif

                      <a href="{{ route('home') }}#products" class="hero-button mt-7 px-8 py-3.5">
                         {{ __('home.hero.cta') }}
                    </a>
                    <div data-hero-bee-swoop
                     class="pointer-events-none absolute left-0 top-0 w-14 select-none sm:w-16">
                    <img src="{{ asset('images/h1-rev-img-2.png') }}"
                         alt=""
                         aria-hidden="true"
                         data-hero-bee-sprite
                         class="block w-full">
               </div>
               </div>
          </div>
     </div>
</section>
