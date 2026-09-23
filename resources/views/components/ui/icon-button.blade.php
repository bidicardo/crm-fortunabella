@props([
    'icon',
    'label',
    'variant' => 'secondary',
])

@php
    $base = 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'secondary' => 'text-ink hover:bg-surface',
        'danger' => 'text-error hover:bg-surface',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['secondary']);
@endphp

<button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => $classes]) }}>
    <x-icon :name="$icon" class="h-5 w-5" />
</button>
