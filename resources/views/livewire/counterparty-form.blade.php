{{-- Создание контрагента. Та же компоновка, что у карточки (counterparty-show): слева данные по два
     в строке, длинные тексты отдельным блоком. Контактные лица и правка — в карточке после создания. --}}
<form wire:submit="save" class="min-w-0 max-w-6xl space-y-4">
    <div class="grid gap-4 lg:grid-cols-3 lg:items-start">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-ui.card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.input id="name" label="Название *" type="text" wire:model="name" required />
                    </div>
                    <x-ui.input id="type" label="Тип" type="text" wire:model="type" placeholder="Например: ивент-агентство, ресторан" />
                    <x-ui.select id="stage" label="Этап" wire:model="stage">
                        @foreach ($stages as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input id="phone" label="Телефон" type="tel" wire:model="phone" placeholder="+7 917 123-45-67" />
                    <x-ui.input id="email" label="Email" type="email" wire:model="email" />
                    <x-ui.input id="social" label="Соцсеть или мессенджер" type="text" wire:model="social" />
                    <x-ui.input id="telegram" label="Telegram-канал или группа" type="text" wire:model="telegram" />
                    <x-ui.input id="website" label="Сайт" type="text" wire:model="website" />
                    <x-ui.input id="cooperation_started_at" label="Дата начала сотрудничества" type="date" wire:model="cooperation_started_at" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="space-y-4">
                    <x-ui.input id="address" label="Адрес" type="text" wire:model="address" />
                    <x-ui.textarea id="cooperation_terms" label="Условия сотрудничества" rows="4" wire:model="cooperation_terms" />
                </div>
            </x-ui.card>
        </div>
    </div>

    <x-ui.button type="submit">Сохранить</x-ui.button>
</form>
