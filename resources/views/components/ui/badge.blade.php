@props([
    'tone' => 'neutral',
])

@php
    $colors = [
        'neutral' => 'var(--color-ink-secondary)',
        'success' => 'var(--color-success)',
        'warning' => 'var(--color-warning)',
        'error' => 'var(--color-error)',
        'info' => 'var(--color-info)',
        'stage-new' => 'var(--color-stage-new)',
        'stage-inwork' => 'var(--color-stage-inwork)',
        'stage-booked' => 'var(--color-stage-booked)',
        'stage-done' => 'var(--color-stage-done)',
        'stage-refused' => 'var(--color-stage-refused)',
    ];
    $color = $colors[$tone] ?? $colors['neutral'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-sm px-2 py-0.5 text-caption font-medium']) }}
    style="color: {{ $color }}; background-color: color-mix(in srgb, {{ $color }} 14%, var(--color-surface));"
>{{ $slot }}</span>
