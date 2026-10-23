{{-- Создание сделки (задача 27) — в компоновке карточки (deal-show): слева основное, справа этап и детали.
     Обязательны название и клиент: найти поиском или «+ Новый клиент» прямо здесь (с предупреждением о дубле). --}}
<form wire:submit="save" class="min-w-0 max-w-6xl space-y-4" x-data x-on:duplicates-found.window="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
    @if ($duplicateClients->isNotEmpty())
        <x-ui.alert kind="warning">
            <div class="space-y-2">
                <p class="font-semibold">Возможно, такой клиент уже есть — совпадает телефон:</p>
                <ul class="space-y-1">
                    @foreach ($duplicateClients as $duplicate)
                        <li wire:key="dup-{{ $duplicate->id }}" class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('clients.show', $duplicate) }}" target="_blank" class="focus-ring link break-words">{{ $duplicate->name }}</a>
                            <span class="text-caption text-ink-secondary">{{ $duplicate->phone }}</span>
                            <x-ui.button variant="secondary" wire:click="pickClient({{ $duplicate->id }})">Выбрать этого клиента</x-ui.button>
                        </li>
                    @endforeach
                </ul>
                <x-ui.button variant="secondary" wire:click="save(true)">Всё равно создать нового клиента</x-ui.button>
            </div>
        </x-ui.alert>
    @endif

    <div class="grid gap-4 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
        {{-- Левая колонка: основное --}}
        <x-ui.card class="min-w-0 space-y-4">
            <x-ui.input id="title" label="Название *" wire:model="title" required placeholder="Например: Свадьба Ивановых" />

            {{-- Клиент: выбранный, поиск или новый --}}
            <div class="space-y-2">
                <p class="text-body text-ink">Клиент *</p>
                @if ($client)
                    <div class="flex items-start gap-2 rounded-md border border-line bg-surface px-3 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-semibold">{{ $client->name }}</p>
                            @if ($client->phone)
                                <p class="text-caption text-ink-secondary">{{ $client->phone }}</p>
                            @endif
                        </div>
                        <x-ui.icon-button icon="close" label="Выбрать другого клиента" wire:click="clearClient" />
                    </div>
                @elseif ($newClient)
                    <div class="space-y-3 rounded-md border border-line p-3">
                        <x-ui.input id="newClientName" label="Имя клиента *" wire:model="newClientName" />
                        <x-ui.input id="newClientPhone" label="Телефон" type="tel" placeholder="+7 917 123-45-67" wire:model="newClientPhone" />
                        <x-ui.button variant="secondary" wire:click="cancelNewClient">Найти существующего</x-ui.button>
                    </div>
                @else
                    @include('livewire.deals.party-picker', ['id' => 'client-search', 'label' => 'Найти клиента', 'model' => 'clientSearch', 'search' => $clientSearch, 'options' => $clientOptions, 'pick' => 'pickClient', 'placeholder' => 'Имя или телефон'])
                    <x-ui.button variant="secondary" icon="plus" wire:click="startNewClient">Новый клиент</x-ui.button>
                @endif
                @error('client_id')
                    <p class="text-caption text-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Контрагент: необязателен --}}
            <div class="space-y-2">
                <p class="text-body text-ink">Контрагент</p>
                @if ($counterparty)
                    <div class="flex items-start gap-2 rounded-md border border-line bg-surface px-3 py-2">
                        <p class="min-w-0 flex-1 break-words font-semibold">{{ $counterparty->name }}</p>
                        <x-ui.icon-button icon="close" label="Убрать контрагента" wire:click="clearCounterparty" />
                    </div>
                @else
                    @include('livewire.deals.party-picker', ['id' => 'counterparty-search', 'label' => 'Найти контрагента', 'model' => 'counterpartySearch', 'search' => $counterpartySearch, 'options' => $counterpartyOptions, 'pick' => 'pickCounterparty', 'placeholder' => 'Название, телефон, контакт'])
                @endif
            </div>

            <x-ui.input id="amount" label="Сумма, ₽" type="number" min="0" inputmode="numeric" placeholder="Целые рубли" wire:model="amount" />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <div class="flex items-end gap-1">
                    <div class="min-w-0 flex-1">
                        <x-ui.input id="event_date" label="Дата проведения" type="date" wire:model.live="event_date" />
                    </div>
                    {{-- Занятая дата — предупреждение, сохранение не блокируется --}}
                    <div class="pb-2"><x-deal-date-conflict :deals="$sameDay" /></div>
                </div>
                <x-ui.input id="event_time" label="Время" type="time" wire:model="event_time" />
            </div>

            <x-ui.select id="lead_source" label="Источник лида" wire:model.live="lead_source">
                <option value="">—</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->value }}">{{ $source->label() }}</option>
                @endforeach
            </x-ui.select>
            @if ($lead_source === 'other')
                <x-ui.input id="lead_source_other" label="Источник: свой вариант" wire:model="lead_source_other" />
            @endif

            <x-ui.select id="sold_by_id" label="Кто продал" wire:model="sold_by_id">
                <option value="">—</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </x-ui.select>

            <div class="space-y-3 border-t border-line pt-4">
                <h2 class="text-h4 font-semibold">Предоплата</h2>
                <x-ui.input id="prepayment_amount" label="Сумма предоплаты, ₽" type="number" min="0" inputmode="numeric" placeholder="Целые рубли" wire:model="prepayment_amount" />
                <x-ui.select id="prepayment_holder_id" label="Держатель предоплаты" wire:model="prepayment_holder_id">
                    <option value="">—</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.switch id="prepayment_paid" label="Предоплата оплачена" wire:model="prepayment_paid" />
            </div>
        </x-ui.card>

        {{-- Правая область: этап и детали --}}
        <div class="min-w-0 space-y-4">
            <x-ui.card>
                <x-ui.select id="stage" label="Этап" wire:model="stage">
                    @foreach ($stages as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.card>

            <x-ui.card title="Детали" class="space-y-4">
                <fieldset>
                    <legend class="mb-1 text-body text-ink">Типы столов</legend>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        @foreach ($tableTypes as $type)
                            <x-ui.checkbox id="table-type-{{ $type->value }}" :label="$type->label()" value="{{ $type->value }}" wire:model="table_types" />
                        @endforeach
                    </div>
                    @error('table_types.*')
                        <p class="text-caption text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <x-ui.select id="durationChoice" label="Длительность" wire:model.live="durationChoice">
                            <option value="">—</option>
                            @foreach (range(1, 10) as $h)
                                <option value="{{ $h }}">{{ $h }} ч</option>
                            @endforeach
                            <option value="other">Другое</option>
                        </x-ui.select>
                        @if ($durationChoice === 'other')
                            <x-ui.input id="duration_hours" type="number" min="1" max="12" aria-label="Длительность, часов" placeholder="Часов (до 12)" wire:model="duration_hours" />
                        @else
                            @error('duration_hours')
                                <p class="text-caption text-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                    <x-ui.input id="guests" label="Количество гостей" type="number" min="0" inputmode="numeric" wire:model="guests" />
                </div>

                <x-ui.input id="address" label="Адрес проведения" wire:model="address" />
                <x-ui.textarea id="event_kind" label="Характер мероприятия" rows="3" placeholder="Свадьба, корпоратив, день рождения…" wire:model="event_kind" />
            </x-ui.card>

            <x-ui.card>
                <x-ui.textarea id="notes" label="Примечания" rows="4" wire:model="notes" />
            </x-ui.card>

            <x-ui.button type="submit">Сохранить</x-ui.button>
        </div>
    </div>
</form>
