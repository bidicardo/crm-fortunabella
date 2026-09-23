@props([
    'icon' => null,
    'title',
    'text' => null,
])

{{-- Без рамки: живёт и отдельным блоком, и внутри ячейки таблицы. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-2 px-6 py-8 text-center']) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-8 w-8 text-ink-secondary" />
    @endif

    <p class="text-h4 font-semibold text-ink">{{ $title }}</p>

    @if ($text)
        <p class="text-caption text-ink-secondary">{{ $text }}</p>
    @endif
</div>
