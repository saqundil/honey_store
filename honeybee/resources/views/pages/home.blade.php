{{-- resources/views/pages/home.blade.php --}}
@extends('layouts.app')

@section('content')

    {{-- 1. Hero --}}
    <x-hero />

    {{-- 2. Mission + News --}}
    <section class="py-16 lg:py-24 bg-honey-cream">
        <div class="max-w-[1300px] mx-auto px-6">
            <div class="flex flex-col lg:flex-row gap-10 lg:gap-0">
                <x-mission />
                <x-news />
            </div>
        </div>
    </section>

    {{-- 3. Products --}}
    <x-products />

    {{-- 4. Testimonial + Gallery --}}
    <x-testimonial />

    {{-- 6. Newsletter + Types of Honey --}}
    <x-newsletter />

@endsection
