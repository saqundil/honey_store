{{-- resources/views/components/gallery.blade.php --}}
@props([
    'images' => trans('home.gallery.images')
])

<section class="py-16 bg-honey-cream">
    <div class="max-w-[1300px] mx-auto px-6">

        {{-- Section Header --}}
        <div class="text-center mb-12 flex flex-col gap-4">
            <h2 class="font-condensed font-bold text-4xl md:text-5xl uppercase text-honey-dark leading-tight">
                {{ __('home.gallery.heading') }}
            </h2>
            <p class="text-honey-grey text-base md:text-lg leading-relaxed max-w-2xl mx-auto">
                {{ __('home.gallery.description') }}
            </p>
        </div>

        {{-- Image Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($images as $image)
            <a href="{{ asset($image['src']) }}"
               class="block overflow-hidden group aspect-video">
                <img src="{{ asset($image['src']) }}"
                     alt="{{ $image['alt'] }}"
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
            </a>
            @endforeach
        </div>

    </div>
</section>
