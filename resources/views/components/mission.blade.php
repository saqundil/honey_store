{{-- resources/views/components/mission.blade.php --}}
<article class="home-surface home-card-hover flex w-full flex-col gap-6 p-5 sm:p-6 lg:p-7">
	<div>
		<span class="home-eyebrow">{{ app()->isLocale('ar') ? 'رسالتنا' : 'Our mission' }}</span>
		<h2 class="mt-3 home-title max-w-[14ch]">{{ __('home.mission.heading') }}</h2>
	</div>

    {{-- Video / Image Thumbnail --}}
    <div class="about-figure relative overflow-hidden rounded-[1.7rem] border border-[#2c1b0b]/8 bg-[#f3eeea] shadow-[0_18px_40px_rgba(44,27,11,0.08)]">
        <video class="w-full aspect-video object-cover"
               controls
               playsinline
               preload="metadata"
               poster="{{ asset('images/yh.png') }}">
            <source src="{{ asset('videos/' . rawurlencode('WhatsApp Video 2026-02-28 at 9.58.41 PM.mp4')) }}" type="video/mp4">
            {{ __('home.mission.browser_unsupported') }}
        </video>
    </div>

    {{-- Body Text --}}
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_12rem] lg:items-start">
        <div class="flex flex-col gap-6 home-copy">
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

		<div class="home-surface-soft p-5">
			<p class="home-eyebrow text-[0.7rem]">{{ app()->isLocale('ar') ? 'جوهر الفكرة' : 'Core idea' }}</p>
			<p class="mt-3 text-sm leading-7 text-honey-grey">{{ app()->isLocale('ar') ? 'نربط بين أصل العسل وقيمته اليومية في تجربة أوضح وأكثر هدوءًا.' : 'We connect the honey’s origin to its everyday value through a clearer, calmer experience.' }}</p>
			<div class="mt-5">
				<img src="{{ asset('images/h1-img2.png') }}"
					 alt="{{ __('home.mission.signature_alt') }}"
					 class="h-11 w-auto">
			</div>
		</div>
    </div>

</article>
