@props([
    'kind' => 'info',
    'title' => null,
])

@php
    $icons = ['success' => 'success', 'warning' => 'warning', 'error' => 'error', 'info' => 'info'];
    $colors = [
        'success' => 'var(--color-success)',
        'warning' => 'var(--color-warning)',
        'error' => 'var(--color-error)',
        'info' => 'var(--color-info)',
    ];
    $color = $colors[$kind] ?? $colors['info'];
    $icon = $icons[$kind] ?? $icons['info'];
@endphp

<div
    role="{{ in_array($kind, ['error', 'warning'], true) ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg border-s-4 p-3 text-body text-ink sm:p-4']) }}
    style="border-color: {{ $color }}; background-color: color-mix(in srgb, {{ $color }} 12%, var(--color-surface));"
>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0" style="color: {{ $color }}" />

    <div class="min-w-0 space-y-1">
        @if ($title)
            <p class="text-h4 font-semibold" style="color: {{ $color }}">{{ $title }}</p>
        @endif
        <div class="break-words">{{ $slot }}</div>
    </div>
</div>
