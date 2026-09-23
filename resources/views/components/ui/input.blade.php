@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'error' => null,
    'hint' => null,
    'icon' => null,
])

@php
    $id ??= $name;
    // Ошибка ищется по name, а без него — по id: в формах Livewire name не нужен,
    // а у полей пароля его и нельзя ставить (tests/Feature/SecurityTest.php).
    $key = $name ?? $id;
    $error ??= ($key && isset($errors) ? ($errors->first($key) ?: null) : null);
    $classes = 'focus-ring w-full rounded-md border bg-surface py-2 text-body text-ink placeholder:text-ink-muted disabled:cursor-not-allowed disabled:opacity-50 '
        .($icon ? 'pl-9 pr-3 ' : 'px-3 ')
        .($error ? 'border-error' : 'border-line');
@endphp

<div>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="mb-1 block text-body text-ink">{{ $label }}</label>
    @endif

    <div class="relative">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
        @endif

        <input
            @if ($id) id="{{ $id }}" @endif
            @if ($name) name="{{ $name }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->merge(['class' => $classes]) }}
        >
    </div>

    @if ($error)
        <p class="mt-1 text-caption text-error">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1 text-caption text-ink-secondary">{{ $hint }}</p>
    @endif
</div>
