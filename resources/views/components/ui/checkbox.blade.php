@props([
    'label' => null,
    'id' => null,
    'name' => null,
])

@php
    $id ??= $name;
@endphp

{{-- Подпись — label или слот (слот нужен, когда в подписи разметка). --}}
<label @if ($id) for="{{ $id }}" @endif class="flex min-h-11 items-center gap-2 text-body text-ink has-[:disabled]:cursor-not-allowed has-[:disabled]:text-ink-muted">
    <input
        type="checkbox"
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'focus-ring h-4 w-4 shrink-0 rounded-sm accent-accent disabled:cursor-not-allowed']) }}
    >
    @if ($slot->isNotEmpty())
        <span class="min-w-0 break-words">{{ $slot }}</span>
    @elseif ($label)
        <span class="min-w-0 break-words">{{ $label }}</span>
    @endif
</label>
