{{-- resources/views/components/news.blade.php --}}
@props([
    'articles' => trans('home.news.articles')
])

<aside class="home-surface flex w-full flex-col gap-6 p-6 sm:p-7 lg:p-8">
	<div>
		<span class="home-eyebrow">{{ app()->isLocale('ar') ? 'مستجداتنا' : 'Latest notes' }}</span>
		<h2 class="mt-3 home-title max-w-[10ch]">{{ __('home.news.heading') }}</h2>
	</div>

    {{-- Article List --}}
    <ul class="home-divider-list flex flex-col">
        @foreach ($articles as $article)
        <li class="py-5 first:pt-0 last:pb-0">
            <article class="home-surface-soft home-card-hover flex flex-col gap-3 p-5">
            {{-- Date --}}
            <span class="home-chip-soft w-fit text-[0.66rem] tracking-[0.18em] text-[#2c1b0b]">
                {{ $article['date'] }}
            </span>

            {{-- Title --}}
            <a href="#"
               class="font-condensed text-[1.35rem] font-bold uppercase leading-tight text-honey-dark transition-colors hover:text-honey-orange">
                {{ $article['title'] }}
            </a>

            {{-- Excerpt --}}
            <p class="text-sm leading-7 text-honey-muted">
                {{ $article['excerpt'] }}
            </p>
			</article>
        </li>
        @endforeach
    </ul>

    {{-- Show More Link --}}
    <a href="{{ url('/blog') }}"
       class="home-link mt-1 w-fit">
        {{ __('home.news.show_more') }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</aside>
