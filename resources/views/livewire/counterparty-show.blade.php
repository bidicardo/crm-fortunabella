{{-- Карточка контрагента в компоновке карточки клиента (client-show, задача 19а): название, этап и
     смена этапа сверху; на компьютере две независимые колонки: слева данные, адрес с условиями и
     контактные лица, справа даты и история. На телефоне одна колонка в том же порядке. Места будущих
     блоков: «Сделки» (Фаза 4) — слева; «Задачи» и «Документы» (Фазы 6, 7) — справа под историей.
     Правка — по одному полю прямо здесь, щелчком по нему (отдельной страницы правки нет). --}}
@php
    $error = $errors->first('value') ?: null;
@endphp

<div class="min-w-0 max-w-6xl space-y-4">
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="space-y-3">
        @if ($editing === 'name')
            <div class="flex max-w-xl items-start gap-1">
                <div class="min-w-0 flex-1">
                    <x-ui.input id="edit-name" aria-label="Название" wire:model="value" wire:keydown.enter.prevent="save" wire:keydown.escape="cancel"
                        :error="$error" x-init="$nextTick(() => $el.focus())" />
                </div>
                <x-ui.icon-button icon="check" label="Сохранить" wire:click="save" />
                <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
            </div>
        @else
            <h1 class="min-w-0 text-h3 font-bold">
                <button type="button" wire:click="edit('name')" class="focus-ring group inline-flex max-w-full items-start gap-2 rounded-md text-left hover:text-accent">
                    <span class="min-w-0 break-words">{{ $counterparty->name }}</span>
                    <x-icon name="edit" class="mt-1 h-4 w-4 text-ink-muted group-hover:text-accent" />
                    <span class="sr-only">Изменить</span>
                </button>
            </h1>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge :tone="$counterparty->stage->tone()">{{ $counterparty->stage->label() }}</x-ui.badge>
            <div class="w-full max-w-60">
                <x-ui.select id="stage" aria-label="Сменить этап" wire:change="changeStage($event.target.value)">
                    @foreach ($stages as $option)
                        <option value="{{ $option->value }}" @selected($option === $counterparty->stage)>{{ $option->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <x-ui.button variant="danger" icon="delete" wire:click="destroy"
                wire:confirm="Удалить контрагента «{{ $counterparty->name }}»{{ $contacts->isNotEmpty() ? ' и его контактных лиц ('.$contacts->count().')' : '' }} вместе с историей изменений? Восстановить будет нельзя.">Удалить</x-ui.button>
        </div>

        @error('delete')
            <x-ui.alert kind="error">{{ $message }}</x-ui.alert>
        @enderror
    </div>

    <div class="grid gap-4 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-ui.card>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <x-ui.editable-field label="Тип" field="type" placeholder="Например: ивент-агентство, ресторан" :editing="$editing === 'type'">{{ $counterparty->type }}</x-ui.editable-field>
                    <x-ui.editable-field label="Дата начала сотрудничества" field="cooperation_started_at" type="date" :editing="$editing === 'cooperation_started_at'">{{ $counterparty->cooperation_started_at?->format('d.m.Y') }}</x-ui.editable-field>
                    <x-ui.editable-field label="Телефон" field="phone" type="tel" placeholder="+7 917 123-45-67" :editing="$editing === 'phone'">{{ $counterparty->phone }}</x-ui.editable-field>
                    <x-ui.editable-field label="Email" field="email" type="email" :editing="$editing === 'email'">{{ $counterparty->email }}</x-ui.editable-field>
                    <x-ui.editable-field label="Соцсеть / мессенджер" field="social" :editing="$editing === 'social'">{{ $counterparty->social }}</x-ui.editable-field>
                    <x-ui.editable-field label="Telegram-канал или группа" field="telegram" :editing="$editing === 'telegram'">{{ $counterparty->telegram }}</x-ui.editable-field>
                    <x-ui.editable-field label="Сайт" field="website" :editing="$editing === 'website'">{{ $counterparty->website }}</x-ui.editable-field>
                </dl>
            </x-ui.card>

            <x-ui.card>
                <dl class="grid gap-4">
                    <x-ui.editable-field label="Адрес" field="address" :editing="$editing === 'address'">{{ $counterparty->address }}</x-ui.editable-field>
                    <x-ui.editable-field label="Условия сотрудничества" field="cooperation_terms" type="textarea" :editing="$editing === 'cooperation_terms'">{{ $counterparty->cooperation_terms }}</x-ui.editable-field>
                </dl>
            </x-ui.card>

            <x-ui.card title="Контактные лица">
                <div class="space-y-4">
                    @forelse ($contacts as $contact)
                        <livewire:counterparty-contact-card :contact="$contact" :key="'contact-'.$contact->id" />
                    @empty
                        @unless ($addingContact)
                            <p class="text-ink-secondary">Контактных лиц пока нет.</p>
                        @endunless
                    @endforelse

                    @if ($addingContact)
                        <form wire:submit="saveContact" class="space-y-4 rounded-md border border-line p-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <x-ui.input id="contact-full_name" label="ФИО *" wire:model="contact.full_name" :error="$errors->first('contact.full_name') ?: null" required x-init="$nextTick(() => $el.focus())" />
                                </div>
                                <x-ui.input id="contact-position_title" label="Должность" wire:model="contact.position_title" :error="$errors->first('contact.position_title') ?: null" />
                                <x-ui.input id="contact-phone" label="Телефон" type="tel" placeholder="+7 917 123-45-67" wire:model="contact.phone" :error="$errors->first('contact.phone') ?: null" />
                                <x-ui.input id="contact-email" label="Email" type="email" wire:model="contact.email" :error="$errors->first('contact.email') ?: null" />
                                <x-ui.input id="contact-social" label="Соцсеть или мессенджер" wire:model="contact.social" :error="$errors->first('contact.social') ?: null" />
                                <x-ui.input id="contact-contact_time" label="Удобное время для связи" wire:model="contact.contact_time" :error="$errors->first('contact.contact_time') ?: null" />
                            </div>
                            <x-ui.textarea id="contact-notes" label="Примечание" rows="3" wire:model="contact.notes" :error="$errors->first('contact.notes') ?: null" />
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button type="submit">Сохранить</x-ui.button>
                                <x-ui.button variant="secondary" wire:click="cancelContact">Отмена</x-ui.button>
                            </div>
                        </form>
                    @else
                        <x-ui.button variant="secondary" icon="plus" wire:click="addContact">Добавить контактное лицо</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        </div>

        <div class="min-w-0 space-y-4">
            <x-ui.card>
                <dl class="grid gap-4">
                    <x-ui.field label="Создан">{{ $counterparty->created_at->format('d.m.Y H:i') }}</x-ui.field>
                    <x-ui.field label="Изменён">{{ $counterparty->updated_at->format('d.m.Y H:i') }}</x-ui.field>
                </dl>
            </x-ui.card>

            <x-activity-history :subject="$counterparty" :logs="$logs" :has-more="$hasMoreHistory" :expanded="$historyExpanded" created-label="Контрагент создан" />
        </div>
    </div>
</div>
