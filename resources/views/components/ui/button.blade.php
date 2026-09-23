@props([
    'variant' => 'primary',
    'icon' => null,
    'iconPosition' => 'left',
    'href' => null,
])

@php
    // px-* задаётся внутри каждого варианта, а не в $base: у двух классов с
    // одинаковой специфичностью (px-4 из $base и px-0 из варианта) порядок
    // в списке классов не определяет победителя в CSS Tailwind.
    $base = 'focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-md py-2 text-body font-semibold transition disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'px-4 bg-accent-fill text-white hover:bg-accent-hover active:bg-accent-active',
        'secondary' => 'px-4 border border-line text-ink hover:bg-line/50',
        'danger' => 'px-4 bg-danger text-white hover:brightness-90 active:brightness-75',
        'text' => 'text-accent underline-offset-2 hover:underline',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left') <x-icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right') <x-icon :name="$icon" class="h-4 w-4" /> @endif
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left') <x-icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right') <x-icon :name="$icon" class="h-4 w-4" /> @endif
    </button>
@endif
