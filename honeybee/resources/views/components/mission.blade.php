{{-- resources/views/components/mission.blade.php --}}
<article class="w-full lg:w-7/12 flex flex-col gap-6 pr-0 lg:pr-10">

    {{-- Video / Image Thumbnail --}}
    <div class="relative overflow-hidden rounded-[32px] bg-black shadow-lg">
        <video class="w-full aspect-video object-cover"
               controls
               playsinline
               preload="metadata"
               poster="{{ asset('images/yh.png') }}">
            <source src="{{ asset('videos/' . rawurlencode('WhatsApp Video 2026-02-28 at 9.58.41 PM.mp4')) }}" type="video/mp4">
            {{ __('home.mission.browser_unsupported') }}
        </video>
    </div>

    {{-- Section Heading --}}
    <h2 class="font-condensed font-bold text-3xl md:text-4xl uppercase text-honey-dark leading-tight">
        {{ __('home.mission.heading') }}
    </h2>

    {{-- Body Text --}}
    <div class="flex flex-col gap-6 text-honey-grey text-base md:text-lg leading-relaxed">
        <p>
            {{ __('home.mission.paragraph_1_before') }}
            <strong class="font-bold text-honey-grey">{{ __('home.mission.population') }}</strong>
            {{ __('home.mission.paragraph_1_middle') }}
            <strong class="font-bold text-honey-grey">{{ __('home.mission.awareness') }}</strong>
            <strong class="font-bold text-honey-grey">{{ __('home.mission.community') }}</strong>
        </p>
        <p>
            {{ __('home.mission.paragraph_2') }}
        </p>
    </div>

    {{-- Signature --}}
    <div>
        <img src="{{ asset('images/h1-img2.png') }}"
             alt="{{ __('home.mission.signature_alt') }}"
             class="h-11 w-auto">
    </div>

</article>
