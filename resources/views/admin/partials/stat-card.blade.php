@props([
    'label',
    'value',
    'tone' => 'default',
])

@php
    $tones = [
        'default' => 'from-white to-honey-cream border-black/5',
        'gold' => 'from-honey-gold/20 to-white border-honey-gold/30',
        'dark' => 'from-honey-dark to-honey-grey border-honey-dark/80 text-white',
    ];

    $classes = $tones[$tone] ?? $tones['default'];
@endphp

<div {{ $attributes->class(['rounded-[1.75rem] border bg-gradient-to-br p-6 shadow-sm', $classes]) }}>
    <p class="text-sm font-semibold {{ $tone === 'dark' ? 'text-white/65' : 'text-honey-muted' }}">{{ $label }}</p>
    <p class="mt-3 text-3xl font-bold {{ $tone === 'dark' ? 'text-white' : 'text-honey-dark' }}">{{ $value }}</p>
</div>