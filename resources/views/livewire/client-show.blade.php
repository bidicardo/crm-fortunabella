{{-- Компоновка по референсу 6 (docs/design/refs/06-client-card.png, задача 19а): имя и кнопки сверху,
     на компьютере две независимые колонки (блоки своей высоты): слева данные и примечания, справа
     даты и история. На телефоне одна колонка в том же порядке. Места будущих блоков: «Сделки»
     (Фаза 4) — слева под примечаниями; «Задачи» и «Документы» (Фазы 6, 7) — справа под историей.
     Пустых заглушек нет. Страница создания (client-form) повторяет эту компоновку.
     Правка — по одному полю прямо здесь, щелчком по нему (отдельной страницы правки нет). --}}
@php
    $archived = $client->isArchived();
    $error = $errors->first('value') ?: null;
@endphp

<div class="min-w-0 max-w-6xl space-y-4">
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($archived)
        <x-ui.alert kind="warning">
            <div class="space-y-1">
                <p>Карточка архивирована. Она доступна только для чтения.</p>
                @if ($client->mergedInto)
                    <p>
                        Влит в клиента <a href="{{ route('clients.show', $client->mergedInto) }}" class="focus-ring link font-medium">{{ $client->mergedInto->name }}</a>@if ($client->mergedBy), {{ $client->mergedBy->name }}@endif, {{ $client->merged_at->format('d.m.Y H:i') }}.
                    </p>
                @endif
            </div>
        </x-ui.alert>
    @endif

    <div class="space-y-3">
        @if ($editing === 'name')
            <div class="flex max-w-xl items-start gap-1">
                <div class="min-w-0 flex-1">
                    <x-ui.input id="edit-name" aria-label="Имя" wire:model="value" wire:keydown.enter.prevent="save" wire:keydown.escape="cancel"
                        :error="$error" x-init="$nextTick(() => $el.focus())" />
                </div>
                <x-ui.icon-button icon="check" label="Сохранить" wire:click="save" />
                <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
            </div>
        @elseif ($archived)
            <h1 class="min-w-0 break-words text-h3 font-bold">{{ $client->name }}</h1>
        @else
            <h1 class="min-w-0 text-h3 font-bold">
                <button type="button" wire:click="edit('name')" class="focus-ring group inline-flex max-w-full items-start gap-2 rounded-md text-left hover:text-accent">
                    <span class="min-w-0 break-words">{{ $client->name }}</span>
                    <x-icon name="edit" class="mt-1 h-4 w-4 text-ink-muted group-hover:text-accent" />
                    <span class="sr-only">Изменить</span>
                </button>
            </h1>
        @endif

        @unless ($archived)
            <div class="flex flex-wrap gap-2">
                <x-ui.button variant="secondary" icon="merge" :href="route('clients.merge', $client)">Объединить с…</x-ui.button>
                <x-ui.button variant="danger" icon="delete" wire:click="destroy"
                    wire:confirm="Удалить клиента «{{ $client->name }}»{{ $duplicatesCount ? ' и влитые в него дубли ('.$duplicatesCount.')' : '' }} вместе с историей изменений? Восстановить будет нельзя.">Удалить</x-ui.button>
            </div>
        @endunless

        @error('delete')
            <x-ui.alert kind="error">{{ $message }}</x-ui.alert>
        @enderror
    </div>

    <div class="grid gap-4 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-ui.card>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <x-ui.editable-field label="Телефон" field="phone" type="tel" placeholder="+7 917 123-45-67" :editing="$editing === 'phone'" :readonly="$archived">{{ $client->phone }}</x-ui.editable-field>
                    <x-ui.editable-field label="Email" field="email" type="email" :editing="$editing === 'email'" :readonly="$archived">{{ $client->email }}</x-ui.editable-field>
                    <x-ui.editable-field label="Соцсеть / мессенджер" field="social" :editing="$editing === 'social'" :readonly="$archived">{{ $client->social }}</x-ui.editable-field>
                    <x-ui.editable-field label="Тип клиента" field="legal_type" type="select" :editing="$editing === 'legal_type'" :readonly="$archived">
                        {{ $client->legal_type?->label() }}
                        <x-slot:options>
                            <option value="">—</option>
                            @foreach ($legalTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </x-slot:options>
                    </x-ui.editable-field>
                    <x-ui.editable-field label="Роль" field="role" placeholder="Например: невеста, организатор" :editing="$editing === 'role'" :readonly="$archived">{{ $client->role }}</x-ui.editable-field>
                    <x-ui.editable-field label="Удобное время для связи" field="contact_time" :editing="$editing === 'contact_time'" :readonly="$archived">{{ $client->contact_time }}</x-ui.editable-field>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <dl>
                    <x-ui.editable-field label="Примечания" field="notes" type="textarea" :editing="$editing === 'notes'" :readonly="$archived">{{ $client->notes }}</x-ui.editable-field>
                </dl>
            </x-ui.card>
        </div>

        <div class="min-w-0 space-y-4">
            <x-ui.card>
                <dl class="grid gap-4">
                    <x-ui.field label="Создан">{{ $client->created_at->format('d.m.Y H:i') }}</x-ui.field>
                    <x-ui.field label="Изменён">{{ $client->updated_at->format('d.m.Y H:i') }}</x-ui.field>
                </dl>
            </x-ui.card>

            <x-activity-history :subject="$client" :logs="$logs" :has-more="$hasMoreHistory" :expanded="$historyExpanded" created-label="Клиент создан" />
        </div>
    </div>
</div>
