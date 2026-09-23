@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'error' => null,
    'hint' => null,
])

@php
    $id = $id ?? $name ?? $attributes->get('id');
    $error ??= ($name && isset($errors) ? ($errors->first($name) ?: null) : null);
    $classes = 'w-full rounded-md border bg-surface px-3 py-2 text-body text-ink placeholder:text-ink-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50 '
        .($error ? 'border-error' : 'border-line');
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-body text-ink">{{ $label }}</label>
    @endif

    <input
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >

    @if ($error)
        <p class="mt-1 text-caption text-error">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-caption text-ink-secondary">{{ $hint }}</p>
    @endif
</div>
