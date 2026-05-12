{{-- resources/views/components/honey-types.blade.php --}}
@props([
    'types' => trans('home.types.items')
])

@php($typesBackground = asset('images/Section_2.png'))

<section class="relative isolate overflow-hidden bg-honey-cream py-16 md:min-h-[32rem] lg:min-h-[38rem] xl:aspect-[1920/1345] xl:min-h-0 xl:py-0">

    <img src="{{ $typesBackground }}"
         alt=""
         aria-hidden="true"
            class="pointer-events-none absolute inset-0 z-[1] h-full w-full object-contain object-center select-none">

        <div class="absolute inset-0 z-0 bg-honey-cream/36"></div>

        <div class="relative z-[2] mx-auto flex min-h-[32rem] max-w-[1300px] items-center px-6 py-16 lg:min-h-[38rem] lg:py-24 xl:h-full xl:min-h-0 xl:py-0">
        <div class="w-full">

        {{-- Section Header --}}
        <div class="mb-12 flex flex-col gap-4 text-center lg:mb-14">
            <h2 class="font-condensed font-bold text-4xl md:text-5xl uppercase text-honey-dark leading-tight">
                {{ __('home.types.heading') }}
            </h2>
            <p class="text-honey-grey text-base md:text-lg leading-relaxed max-w-xl mx-auto">
                {{ __('home.types.description') }}
            </p>
        </div>

        {{-- Type Cards Grid --}}
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            @foreach ($types as $type)
            <article class="group flex items-center gap-6 border border-black/8 bg-honey-card/95 px-8 py-7 shadow-[0_16px_40px_rgba(44,27,11,0.06)] backdrop-blur-sm transition-shadow hover:shadow-[0_20px_46px_rgba(44,27,11,0.1)]">

                {{-- Icon / Image --}}
                <div class="flex-shrink-0">
                    <img src="{{ asset($type['image']) }}"
                         alt="{{ $type['name'] }}"
                         class="w-28 h-auto object-contain">
                </div>

                {{-- Text --}}
                <div class="flex flex-col gap-2">
                    <h3 class="font-condensed font-bold text-2xl uppercase text-honey-dark leading-tight">
                        {{ $type['name'] }}
                    </h3>
                    <p class="text-honey-muted text-sm leading-relaxed">
                        {{ $type['excerpt'] }}
                    </p>
                    <a href="#"
                       class="inline-flex items-center gap-2 font-condensed font-bold text-xs uppercase tracking-widest text-honey-orange hover:text-honey-orange/80 transition-colors">
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
</section>
