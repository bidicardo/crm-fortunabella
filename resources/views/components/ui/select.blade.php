@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'error' => null,
])

@php
    $id = $id ?? $name ?? $attributes->get('id');
    $error ??= ($name && isset($errors) ? ($errors->first($name) ?: null) : null);
    $classes = 'w-full rounded-md border bg-surface px-3 py-2 text-body text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-50 '
        .($error ? 'border-error' : 'border-line');
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-body text-ink">{{ $label }}</label>
    @endif

    <select
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </select>

    @if ($error)
        <p class="mt-1 text-caption text-error">{{ $error }}</p>
    @endif
</div>
