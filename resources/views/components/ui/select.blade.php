@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'error' => null,
    // Цвет этапа (stage-new, stage-inwork…): текст и рамка этого цвета, фон — слабый оттенок (8 %: контраст текста AA в обеих темах)
    'tone' => null,
])

@php
    $id ??= $name;
    $key = $name ?? $id;
    $error ??= ($key && isset($errors) ? ($errors->first($key) ?: null) : null);
    // Правый отступ под стрелку задаёт общее правило select в app.css.
    $classes = 'focus-ring w-full rounded-md border bg-surface px-3 py-2 text-body text-ink disabled:cursor-not-allowed disabled:opacity-50 '
        .($error ? 'border-error' : 'border-line');
    // Цвет этапа приглушается серым на --stage-mute (тёмная тема — 30 %, светлая — 0 %), как линия карточки канбана.
    $color = "color-mix(in srgb, var(--color-{$tone}), var(--color-ink-muted) var(--stage-mute))";
    $style = $tone && ! $error
        ? "color: {$color}; border-color: {$color}; background-color: color-mix(in srgb, {$color} 8%, var(--color-page));"
        : null;
@endphp

<div>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="mb-1 block text-body text-ink">{{ $label }}</label>
    @endif

    <select
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($style) style="{{ $style }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </select>

    @if ($error)
        <p class="mt-1 text-caption text-error">{{ $error }}</p>
    @endif
</div>
