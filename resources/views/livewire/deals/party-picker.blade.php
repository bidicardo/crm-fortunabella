{{-- Поиск клиента или контрагента сделки (DealForm, DealShow — трейт EditsDealParties). Параметры:
     $id (id поля), $label, $model (clientSearch | counterpartySearch), $search (его значение), $options
     (найденные), $pick (метод выбора), $placeholder, $autofocus (в карточке — да). Результаты — кнопки под полем; ищет от 2 символов. --}}
<div class="space-y-2">
    <x-ui.input :id="$id" type="search" icon="search" :aria-label="$label" :placeholder="$placeholder"
        wire:model.live.debounce.300ms="{{ $model }}" :x-init="($autofocus ?? false) ? '$nextTick(() => $el.focus())' : null" />

    @if (mb_strlen(trim($search)) >= 2)
        @if ($options->isEmpty())
            <p class="text-caption text-ink-secondary">Ничего не найдено.</p>
        @else
            <ul class="divide-y divide-line rounded-md border border-line bg-page">
                @foreach ($options as $option)
                    <li wire:key="{{ $id }}-{{ $option->id }}">
                        <button type="button" wire:click="{{ $pick }}({{ $option->id }})"
                            class="focus-ring flex min-h-11 w-full flex-col items-start justify-center px-3 py-2 text-left hover:bg-line/50">
                            <span class="break-words font-medium">{{ $option->name }}</span>
                            @if ($option->phone || $option->email)
                                <span class="text-caption text-ink-secondary">{{ collect([$option->phone, $option->email])->filter()->implode(' · ') }}</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
