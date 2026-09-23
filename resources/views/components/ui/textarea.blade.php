@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'error' => null,
])

@php
    $id ??= $name;
    $key = $name ?? $id;
    $error ??= ($key && isset($errors) ? ($errors->first($key) ?: null) : null);
    $classes = 'focus-ring w-full rounded-md border bg-surface px-3 py-2 text-body text-ink placeholder:text-ink-muted disabled:cursor-not-allowed disabled:opacity-50 '
        .($error ? 'border-error' : 'border-line');
@endphp

<div>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="mb-1 block text-body text-ink">{{ $label }}</label>
    @endif

    <textarea
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >{{ $slot }}</textarea>

    @if ($error)
        <p class="mt-1 text-caption text-error">{{ $error }}</p>
    @endif
</div>
