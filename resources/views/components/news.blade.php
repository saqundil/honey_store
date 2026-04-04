{{-- resources/views/components/news.blade.php --}}
@props([
    'articles' => trans('home.news.articles')
])

<aside class="w-full lg:w-5/12 bg-white p-8 lg:p-10 flex flex-col gap-6">

    {{-- Section Heading --}}
    <h2 class="font-condensed font-bold text-3xl md:text-4xl uppercase text-honey-dark leading-tight">
        {{ __('home.news.heading') }}
    </h2>

    {{-- Article List --}}
    <ul class="flex flex-col divide-y divide-black/10">
        @foreach ($articles as $article)
        <li class="py-6 flex flex-col gap-2">
            {{-- Date --}}
            <span class="font-condensed font-bold text-xs uppercase tracking-widest text-honey-orange">
                {{ $article['date'] }}
            </span>

            {{-- Title --}}
            <a href="#"
               class="font-condensed font-bold text-xl uppercase text-honey-dark leading-tight hover:text-honey-orange transition-colors">
                {{ $article['title'] }}
            </a>

            {{-- Excerpt --}}
            <p class="text-honey-muted text-sm leading-relaxed">
                {{ $article['excerpt'] }}
            </p>
        </li>
        @endforeach
    </ul>

    {{-- Show More Link --}}
    <a href="{{ url('/blog') }}"
       class="inline-flex items-center gap-2 font-condensed font-bold text-xs uppercase tracking-widest text-honey-dark hover:text-honey-orange transition-colors">
        {{ __('home.news.show_more') }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</aside>
