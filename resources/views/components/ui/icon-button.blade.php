@props([
    'icon',
    'label',
    'variant' => 'secondary',
])

@php
    $base = 'focus-ring inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md transition hover:bg-line/50 disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'secondary' => 'text-ink',
        'danger' => 'text-error',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['secondary']);
@endphp

<button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    <x-icon :name="$icon" class="h-5 w-5" />
</button>
