@props([
    'label',
    'multiline' => false,
])

{{-- Поле карточки только для чтения: выглядит как поле формы редактирования. Ставится внутрь <dl>. --}}
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <dt class="mb-1 text-body text-ink">{{ $label }}</dt>
    {{-- Содержимое вплотную к тегам: при whitespace-pre-line любой отступ стал бы пустой строкой --}}
    <dd class="break-words rounded-md border border-line bg-surface px-3 py-2 {{ $multiline ? 'whitespace-pre-line' : '' }}">{{ $slot->hasActualContent() ? $slot : '—' }}</dd>
</div>
