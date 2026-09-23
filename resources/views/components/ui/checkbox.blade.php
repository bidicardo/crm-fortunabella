@props([
    'label' => null,
    'id' => null,
    'name' => null,
])

@php
    $id = $id ?? $name ?? $attributes->get('id');
@endphp

<label for="{{ $id }}" class="flex min-h-11 items-center gap-2 text-body text-ink">
    <input
        type="checkbox"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0 rounded-sm border-line accent-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50']) }}
    >
    @if ($label)
        <span>{{ $label }}</span>
    @endif
</label>
