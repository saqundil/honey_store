{{-- resources/views/components/news.blade.php --}}
@props([
    'items' => trans('home.news.items')
])

<aside class="home-surface flex w-full flex-col gap-6 p-6 sm:p-7 lg:p-8">
	<div>
		<span class="home-eyebrow">{{ __('home.news.eyebrow') }}</span>
		<h2 class="mt-3 home-title max-w-[10ch]">{{ __('home.news.heading') }}</h2>
	</div>

    {{-- Feature List --}}
    <ul class="home-divider-list flex flex-col">
        @foreach ($items as $item)
        <li class="py-5 first:pt-0 last:pb-0">
            <article class="home-surface-soft home-card-hover flex flex-col gap-3 p-5">
            {{-- Badge --}}
            <span class="home-chip-soft w-fit text-[0.66rem] tracking-[0.18em] text-[#2c1b0b]">
                {{ $item['badge'] }}
            </span>

            <h3 class="font-condensed text-[1.35rem] font-bold uppercase leading-tight text-honey-dark">
                {{ $item['title'] }}
            </h3>

            {{-- Description --}}
            <p class="text-sm leading-7 text-honey-muted">
                {{ $item['excerpt'] }}
            </p>
			</article>
        </li>
        @endforeach
    </ul>

    {{-- Learn More Link --}}
    <a href="{{ route('about') }}"
       class="home-link mt-1 w-fit">
        {{ __('home.news.show_more') }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</aside>
