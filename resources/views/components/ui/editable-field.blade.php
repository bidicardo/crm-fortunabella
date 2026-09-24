@props([
    'label',
    'field',
    'editing' => false,
    'readonly' => false,
    'type' => 'text',
    'placeholder' => null,
])

{{-- Поле карточки с правкой по клику (задача 19а). Ставится внутрь <dl>. Livewire-компонент карточки
     должен иметь методы edit(поле), save(), cancel() и черновик $value (образец — ClientShow).
     type: text | tel | email | textarea | select (варианты select — в слоте options).
     Сохранение — Enter (в textarea — Ctrl/Cmd+Enter), кнопка ✓ или выбор варианта в списке;
     отмена — Esc или ✕. Содержимое слота — значение для просмотра, пустое показывается как «—». --}}
@php
    $multiline = $type === 'textarea';
    $id = 'edit-'.$field;
    $empty = ! $slot->hasActualContent();
    $error = isset($errors) ? ($errors->first('value') ?: null) : null;
@endphp

<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <dt class="mb-1 text-body text-ink">
        @if ($editing)
            <label for="{{ $id }}">{{ $label }}</label>
        @else
            {{ $label }}
        @endif
    </dt>
    <dd>
        @if ($editing)
            <div class="flex items-start gap-1">
                <div class="min-w-0 flex-1">
                    @if ($type === 'select')
                        <x-ui.select :id="$id" wire:model="value" wire:change="save" wire:keydown.escape="cancel"
                            :error="$error" x-init="$nextTick(() => $el.focus())">
                            {{ $options ?? '' }}
                        </x-ui.select>
                    @elseif ($multiline)
                        <x-ui.textarea :id="$id" rows="4" wire:model="value" wire:keydown.escape="cancel"
                            wire:keydown.ctrl.enter="save" wire:keydown.meta.enter="save"
                            :error="$error" x-init="$nextTick(() => $el.focus())" />
                    @else
                        <x-ui.input :id="$id" :type="$type" :placeholder="$placeholder" wire:model="value"
                            wire:keydown.enter.prevent="save" wire:keydown.escape="cancel"
                            :error="$error" x-init="$nextTick(() => $el.focus())" />
                    @endif
                </div>
                <x-ui.icon-button icon="check" label="Сохранить" wire:click="save" />
                <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
            </div>
        @elseif ($readonly)
            <div class="break-words rounded-md border border-line bg-surface px-3 py-2 {{ $multiline ? 'whitespace-pre-line' : '' }}">{{ $empty ? '—' : $slot }}</div>
        @else
            <button type="button" wire:click="edit('{{ $field }}')"
                class="focus-ring group flex w-full items-start gap-2 rounded-md border border-line bg-surface px-3 py-2 text-left hover:border-accent">
                <span class="min-w-0 flex-1 break-words {{ $multiline ? 'whitespace-pre-line' : '' }}">{{ $empty ? '—' : $slot }}</span>
                <x-icon name="edit" class="mt-0.5 h-4 w-4 text-ink-muted group-hover:text-accent" />
                <span class="sr-only">Изменить</span>
            </button>
        @endif
    </dd>
</div>
