@props([
    'icon' => null,
    'title',
    'text' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 rounded-lg border border-dashed border-line px-6 py-10 text-center']) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-8 w-8 text-ink-secondary" />
    @endif

    <p class="text-h4 font-semibold text-ink">{{ $title }}</p>

    @if ($text)
        <p class="text-caption text-ink-secondary">{{ $text }}</p>
    @endif
</div>
