{{-- Контактное лицо в карточке контрагента: сверху ФИО, быстрые ссылки (звонок, письмо) и удаление;
     ниже поля с правкой по щелчку, как у карточки. Ссылки tel:/mailto: — отдельно от полей:
     щелчок по полю открывает правку. --}}
@php
    $prefix = 'edit-contact-'.$contact->id.'-';
@endphp

<div class="space-y-3 rounded-md border border-line p-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="break-words font-semibold">{{ $contact->full_name }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-1">
                @if ($contact->phone)
                    <a href="tel:{{ $contact->phone }}" class="focus-ring link">{{ $contact->phone }}</a>
                @endif
                @if ($contact->email)
                    <a href="mailto:{{ $contact->email }}" class="focus-ring link break-all">{{ $contact->email }}</a>
                @endif
            </div>
        </div>
        <x-ui.icon-button icon="delete" label="Удалить контактное лицо" wire:click="delete"
            wire:confirm="Удалить контактное лицо «{{ $contact->full_name }}»? Это действие нельзя отменить." />
    </div>

    <dl class="grid gap-4 sm:grid-cols-2">
        <x-ui.editable-field label="ФИО" field="full_name" :id-prefix="$prefix" :editing="$editing === 'full_name'">{{ $contact->full_name }}</x-ui.editable-field>
        <x-ui.editable-field label="Должность" field="position_title" :id-prefix="$prefix" :editing="$editing === 'position_title'">{{ $contact->position_title }}</x-ui.editable-field>
        <x-ui.editable-field label="Телефон" field="phone" type="tel" placeholder="+7 917 123-45-67" :id-prefix="$prefix" :editing="$editing === 'phone'">{{ $contact->phone }}</x-ui.editable-field>
        <x-ui.editable-field label="Email" field="email" type="email" :id-prefix="$prefix" :editing="$editing === 'email'">{{ $contact->email }}</x-ui.editable-field>
        <x-ui.editable-field label="Соцсеть / мессенджер" field="social" :id-prefix="$prefix" :editing="$editing === 'social'">{{ $contact->social }}</x-ui.editable-field>
        <x-ui.editable-field label="Удобное время для связи" field="contact_time" :id-prefix="$prefix" :editing="$editing === 'contact_time'">{{ $contact->contact_time }}</x-ui.editable-field>
        <x-ui.editable-field class="sm:col-span-2" label="Примечание" field="notes" type="textarea" :id-prefix="$prefix" :editing="$editing === 'notes'">{{ $contact->notes }}</x-ui.editable-field>
    </dl>
</div>
