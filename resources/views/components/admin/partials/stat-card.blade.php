@props([
    'label',
    'value',
    'tone' => 'default',
])

@php
    $tones = [
        'default' => [
            'panel' => 'border-white/70 bg-white/70 text-slate-950 shadow-[0_24px_60px_rgba(15,23,42,0.10)]',
            'halo' => 'bg-[radial-gradient(circle,rgba(99,102,241,0.20),transparent_62%)]',
            'label' => 'text-slate-500',
            'value' => 'text-slate-950',
            'icon' => 'border-slate-200/80 bg-slate-100/80 text-slate-700',
            'dot' => 'bg-indigo-500',
            'note' => 'text-slate-500',
        ],
        'gold' => [
            'panel' => 'border-amber-200/70 bg-[linear-gradient(135deg,rgba(255,250,240,0.9),rgba(255,255,255,0.86))] text-slate-950 shadow-[0_24px_60px_rgba(211,168,99,0.18)]',
            'halo' => 'bg-[radial-gradient(circle,rgba(211,168,99,0.28),transparent_62%)]',
            'label' => 'text-amber-700/80',
            'value' => 'text-slate-950',
            'icon' => 'border-amber-200/80 bg-amber-100/80 text-amber-700',
            'dot' => 'bg-amber-500',
            'note' => 'text-amber-700/80',
        ],
        'dark' => [
            'panel' => 'border-white/10 bg-slate-950/90 text-white shadow-[0_28px_70px_rgba(15,23,42,0.32)]',
            'halo' => 'bg-[radial-gradient(circle,rgba(56,189,248,0.20),transparent_62%)]',
            'label' => 'text-white/50',
            'value' => 'text-white',
            'icon' => 'border-white/10 bg-white/10 text-white',
            'dot' => 'bg-sky-400',
            'note' => 'text-white/60',
        ],
    ];

    $classes = $tones[$tone] ?? $tones['default'];
@endphp

<div {{ $attributes->class(['group relative overflow-hidden rounded-[1.9rem] border p-6 backdrop-blur-2xl transition duration-200 hover:-translate-y-1', $classes['panel']]) }}>
    <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full {{ $classes['halo'] }}"></div>
    <div class="relative flex items-start justify-between gap-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] {{ $classes['label'] }}">{{ $label }}</p>
            <p class="mt-4 text-3xl font-semibold tracking-[-0.05em] {{ $classes['value'] }}">{{ $value }}</p>
        </div>
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border {{ $classes['icon'] }}">
            <span class="h-3 w-3 rounded-full {{ $classes['dot'] }}"></span>
        </span>
    </div>
    <div class="relative mt-6 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] {{ $classes['note'] }}">
        <span class="h-2 w-2 rounded-full {{ $classes['dot'] }}"></span>
        <span>{{ $tone === 'gold' ? 'Live Revenue' : ($tone === 'dark' ? 'Focus Queue' : 'Live Metric') }}</span>
    </div>
</div>