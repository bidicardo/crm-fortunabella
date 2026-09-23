@props([
    'label' => null,
    'id' => null,
    'name' => null,
])

@php
    $id = $id ?? $name ?? $attributes->get('id');
@endphp

<label for="{{ $id }}" class="flex min-h-11 items-center gap-3 text-body text-ink">
    <span class="relative inline-flex h-4 w-7 shrink-0 items-center">
        <input
            type="checkbox"
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        <span class="pointer-events-none absolute inset-0 rounded-full bg-line transition-colors peer-checked:bg-accent-fill peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent peer-disabled:opacity-50"></span>
        <span class="pointer-events-none absolute left-0.5 h-3 w-3 rounded-full bg-white transition-transform peer-checked:translate-x-3"></span>
    </span>
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>
