{{-- Карточка сделки по референсу docs/design/refs/07-deal-card.png (задача 27): на компьютере слева узкая
     колонка основной информации, справа — линия этапов (нажатие меняет этап) и вкладки. На телефоне — одна
     колонка: сначала левая, линия этапов и вкладки листаются вбок внутри своих блоков (relative — sr-only
     и абсолютные элементы не растягивают страницу на iPhone). Правка — по одному полю щелчком. --}}
@php
    $error = $errors->first('value') ?: null;
    $activeTab = array_key_exists($tab, $tabs) ? $tab : 'details';
    $field = 'focus-ring group flex w-full items-start gap-2 rounded-md border border-line bg-surface px-3 py-2 text-left hover:border-accent';
    $stageIndex = array_search($deal->stage, $stages, true);
@endphp

<div class="min-w-0 max-w-6xl space-y-4">
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="grid gap-4 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
        {{-- Левая колонка: основное --}}
        <x-ui.card class="min-w-0 space-y-5">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge :tone="$deal->stage->tone()">{{ $deal->stage->label() }}</x-ui.badge>
                    <span class="text-caption text-ink-secondary">Сделка № {{ $deal->id }}</span>
                </div>

                @if ($editing === 'title')
                    <div class="flex items-start gap-1">
                        <div class="min-w-0 flex-1">
                            <x-ui.input id="edit-title" aria-label="Название" wire:model="value" wire:keydown.enter.prevent="save" wire:keydown.escape="cancel"
                                :error="$error" x-init="$nextTick(() => $el.focus())" />
                        </div>
                        <x-ui.icon-button icon="check" label="Сохранить" wire:click="save" />
                        <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
                    </div>
                @else
                    <h1 class="min-w-0 text-h3 font-bold">
                        <button type="button" wire:click="edit('title')" class="focus-ring group inline-flex max-w-full items-start gap-2 rounded-md text-left hover:text-accent">
                            <span class="min-w-0 break-words">{{ $deal->title }}</span>
                            <x-icon name="edit" class="mt-1 h-4 w-4 text-ink-muted group-hover:text-accent" />
                            <span class="sr-only">Изменить</span>
                        </button>
                    </h1>
                @endif
            </div>

            <dl class="grid gap-4">
                <x-ui.editable-field label="Сумма" field="amount" type="number" placeholder="Целые рубли" :editing="$editing === 'amount'" class="text-h3 font-bold">{{ App\Models\Deal::money($deal->amount) }}</x-ui.editable-field>

                {{-- Клиент: ссылка на карточку и контакты; сменить — поиском --}}
                <div class="min-w-0">
                    <dt class="mb-1 text-body text-ink">Клиент</dt>
                    <dd>
                        @if ($editing === 'client_id')
                            <div class="flex items-start gap-1">
                                <div class="min-w-0 flex-1">
                                    @include('livewire.deals.party-picker', ['id' => 'edit-client', 'label' => 'Найти клиента', 'model' => 'clientSearch', 'search' => $clientSearch, 'options' => $clientOptions, 'pick' => 'pickClient', 'placeholder' => 'Имя или телефон', 'autofocus' => true])
                                    @if ($error)
                                        <p class="mt-1 text-caption text-error">{{ $error }}</p>
                                    @endif
                                </div>
                                <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
                            </div>
                        @else
                            <div class="flex items-start gap-2 rounded-md border border-line bg-surface px-3 py-2">
                                <div class="min-w-0 flex-1 space-y-0.5">
                                    <a href="{{ route('clients.show', $deal->client) }}" class="focus-ring link block break-words font-semibold">{{ $deal->client->name }}</a>
                                    @if ($deal->client->phone)
                                        <a href="tel:{{ $deal->client->phone }}" class="focus-ring link block text-caption">{{ $deal->client->phone }}</a>
                                    @endif
                                    @if ($deal->client->email)
                                        <a href="mailto:{{ $deal->client->email }}" class="focus-ring link block break-all text-caption">{{ $deal->client->email }}</a>
                                    @endif
                                </div>
                                <x-ui.icon-button icon="edit" label="Сменить клиента" wire:click="edit('client_id')" />
                            </div>
                        @endif
                    </dd>
                </div>

                {{-- Контрагент: необязателен --}}
                <div class="min-w-0">
                    <dt class="mb-1 text-body text-ink">Контрагент</dt>
                    <dd>
                        @if ($editing === 'counterparty_id')
                            <div class="flex items-start gap-1">
                                <div class="min-w-0 flex-1">
                                    @include('livewire.deals.party-picker', ['id' => 'edit-counterparty', 'label' => 'Найти контрагента', 'model' => 'counterpartySearch', 'search' => $counterpartySearch, 'options' => $counterpartyOptions, 'pick' => 'pickCounterparty', 'placeholder' => 'Название, телефон, контакт', 'autofocus' => true])
                                </div>
                                @if ($deal->counterparty)
                                    <x-ui.icon-button icon="delete" variant="danger" label="Убрать контрагента" wire:click="clearCounterparty" />
                                @endif
                                <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
                            </div>
                        @else
                            <div class="flex items-start gap-2 rounded-md border border-line bg-surface px-3 py-2">
                                <div class="min-w-0 flex-1">
                                    @if ($deal->counterparty)
                                        <a href="{{ route('counterparties.show', $deal->counterparty) }}" class="focus-ring link block break-words font-semibold">{{ $deal->counterparty->name }}</a>
                                    @else
                                        <span>—</span>
                                    @endif
                                </div>
                                <x-ui.icon-button icon="edit" label="{{ $deal->counterparty ? 'Сменить контрагента' : 'Выбрать контрагента' }}" wire:click="edit('counterparty_id')" />
                            </div>
                        @endif
                    </dd>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <x-ui.editable-field label="Дата проведения" field="event_date" type="date" :editing="$editing === 'event_date'">
                        {{ $deal->event_date?->format('d.m.Y') }}
                        <x-slot:aside><x-deal-date-conflict :deals="$sameDay" /></x-slot:aside>
                    </x-ui.editable-field>
                    <x-ui.editable-field label="Время" field="event_time" type="time" :editing="$editing === 'event_time'">{{ $deal->event_time }}</x-ui.editable-field>
                </div>

                <x-ui.editable-field label="Источник лида" field="lead_source" type="select" :editing="$editing === 'lead_source'">
                    {{ $deal->lead_source?->label() }}
                    <x-slot:options>
                        <option value="">—</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->value }}">{{ $source->label() }}</option>
                        @endforeach
                    </x-slot:options>
                </x-ui.editable-field>
                @if ($deal->lead_source === App\Enums\LeadSource::Other)
                    <x-ui.editable-field label="Источник: свой вариант" field="lead_source_other" :editing="$editing === 'lead_source_other'">{{ $deal->lead_source_other }}</x-ui.editable-field>
                @endif

                <x-ui.editable-field label="Кто продал" field="sold_by_id" type="select" :editing="$editing === 'sold_by_id'">
                    {{ $deal->soldBy?->name }}
                    <x-slot:options>
                        <option value="">—</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </x-slot:options>
                </x-ui.editable-field>
            </dl>

            {{-- Предоплата --}}
            <div class="space-y-3 border-t border-line pt-4">
                <h2 class="text-h4 font-semibold">Предоплата</h2>
                <dl class="grid gap-4">
                    <x-ui.editable-field label="Сумма предоплаты" field="prepayment_amount" type="number" placeholder="Целые рубли" :editing="$editing === 'prepayment_amount'">{{ App\Models\Deal::money($deal->prepayment_amount) }}</x-ui.editable-field>
                    <x-ui.editable-field label="Держатель предоплаты" field="prepayment_holder_id" type="select" :editing="$editing === 'prepayment_holder_id'">
                        {{ $deal->prepaymentHolder?->name }}
                        <x-slot:options>
                            <option value="">—</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </x-slot:options>
                    </x-ui.editable-field>
                </dl>
                <x-ui.switch id="prepayment-paid" label="Предоплата оплачена" :checked="$deal->prepayment_paid" wire:click="togglePrepaymentPaid" />
            </div>

            <div class="space-y-3 border-t border-line pt-4">
                <p class="text-caption text-ink-secondary">
                    Сделка создана {{ $deal->created_at->format('d.m.Y H:i') }} · изменена {{ $deal->updated_at->format('d.m.Y H:i') }}
                </p>
                <x-ui.button variant="danger" icon="delete" wire:click="destroy"
                    wire:confirm="Удалить сделку «{{ $deal->title }}» вместе с историей изменений? Восстановить будет нельзя.">Удалить</x-ui.button>
            </div>
        </x-ui.card>

        {{-- Правая область: линия этапов и вкладки --}}
        <div class="min-w-0 space-y-4">
            <x-ui.card>
                <h2 class="sr-only">Этап сделки</h2>
                {{-- Шевроны: пройденные — фон линии, текущий — заливка цветом этапа (текст цвета страницы: читается в обеих
                     темах), нажатие на любой — переход в этот этап (свободные переходы). --}}
                <div class="relative -mx-1 overflow-x-auto px-1">
                    <ol class="flex min-w-max gap-0.5">
                        @foreach ($stages as $i => $stage)
                            @php
                                $current = $stage === $deal->stage;
                                $clip = $i === 0
                                    ? 'polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%)'
                                    : 'polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%, 10px 50%)';
                                $style = "clip-path: $clip;".($current
                                    ? " background-color: var(--color-{$stage->tone()}); color: var(--color-page);"
                                    : ($i < $stageIndex ? ' background-color: color-mix(in srgb, var(--color-'.$stage->tone().') 18%, var(--color-line));' : ' background-color: var(--color-line);'));
                            @endphp
                            <li wire:key="stage-{{ $stage->value }}">
                                <button type="button" wire:click="changeStage('{{ $stage->value }}')" style="{{ $style }}"
                                    @if ($current) aria-current="step" @endif
                                    class="focus-ring inline-flex min-h-11 items-center whitespace-nowrap py-2 pl-6 pr-6 text-caption {{ $current ? 'font-semibold' : 'text-ink hover:opacity-80' }}">
                                    {{ $stage->label() }}
                                </button>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </x-ui.card>

            <x-ui.card class="min-w-0">
                <div role="tablist" aria-label="Разделы сделки" class="relative -mx-1 mb-4 flex overflow-x-auto border-b border-line px-1">
                    @foreach ($tabs as $key => $label)
                        <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                            class="focus-ring -mb-px min-h-11 shrink-0 whitespace-nowrap border-b-2 px-3 {{ $activeTab === $key ? 'border-accent font-semibold text-ink' : 'border-transparent text-ink-secondary hover:text-ink' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div role="tabpanel" aria-label="{{ $tabs[$activeTab] }}">
                    @if ($activeTab === 'details')
                        <dl class="grid gap-4 sm:grid-cols-2">
                            {{-- Типы столов — множественный выбор флажками --}}
                            <div class="min-w-0 sm:col-span-2">
                                <dt class="mb-1 text-body text-ink">Типы столов</dt>
                                <dd>
                                    @if ($editingTableTypes)
                                        <div class="space-y-2 rounded-md border border-line p-3">
                                            <div class="grid gap-x-4 sm:grid-cols-2">
                                                @foreach ($tableTypeOptions as $type)
                                                    <x-ui.checkbox id="table-type-{{ $type->value }}" :label="$type->label()" value="{{ $type->value }}" wire:model="tableTypes" />
                                                @endforeach
                                            </div>
                                            <div class="flex gap-2">
                                                <x-ui.button wire:click="saveTableTypes">Сохранить</x-ui.button>
                                                <x-ui.button variant="secondary" wire:click="cancelTableTypes">Отмена</x-ui.button>
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" wire:click="editTableTypes" class="{{ $field }}">
                                            <span class="min-w-0 flex-1 break-words">{{ collect($deal->table_types ?? [])->map(fn ($t) => App\Enums\TableType::tryFrom($t)?->label())->filter()->implode(', ') ?: '—' }}</span>
                                            <x-icon name="edit" class="mt-0.5 h-4 w-4 text-ink-muted group-hover:text-accent" />
                                            <span class="sr-only">Изменить</span>
                                        </button>
                                    @endif
                                </dd>
                            </div>

                            {{-- Длительность: 1–10 ч списком или «Другое» — число (до 12) --}}
                            <div class="min-w-0">
                                <dt class="mb-1 text-body text-ink">@if ($editing === 'duration_hours')<label for="edit-duration">Длительность</label>@else Длительность @endif</dt>
                                <dd>
                                    @if ($editing === 'duration_hours')
                                        <div class="flex items-start gap-1">
                                            <div class="min-w-0 flex-1 space-y-2">
                                                <x-ui.select id="edit-duration" wire:model.live="durationChoice" wire:keydown.escape="cancel" x-init="$nextTick(() => $el.focus())">
                                                    <option value="">—</option>
                                                    @foreach (range(1, 10) as $h)
                                                        <option value="{{ $h }}">{{ $h }} ч</option>
                                                    @endforeach
                                                    <option value="other">Другое</option>
                                                </x-ui.select>
                                                @if ($durationChoice === 'other')
                                                    <x-ui.input id="edit-duration-other" type="number" min="1" max="12" aria-label="Длительность, часов" placeholder="Часов (до 12)"
                                                        wire:model="value" wire:keydown.enter.prevent="save" wire:keydown.escape="cancel" :error="$error" />
                                                @elseif ($error)
                                                    <p class="text-caption text-error">{{ $error }}</p>
                                                @endif
                                            </div>
                                            @if ($durationChoice === 'other')
                                                <x-ui.icon-button icon="check" label="Сохранить" wire:click="save" />
                                            @endif
                                            <x-ui.icon-button icon="close" label="Отмена" wire:click="cancel" />
                                        </div>
                                    @else
                                        <button type="button" wire:click="editDuration" class="{{ $field }}">
                                            <span class="min-w-0 flex-1">{{ $deal->duration_hours ? $deal->duration_hours.' ч' : '—' }}</span>
                                            <x-icon name="edit" class="mt-0.5 h-4 w-4 text-ink-muted group-hover:text-accent" />
                                            <span class="sr-only">Изменить</span>
                                        </button>
                                    @endif
                                </dd>
                            </div>

                            <x-ui.editable-field label="Количество гостей" field="guests" type="number" :editing="$editing === 'guests'">{{ $deal->guests }}</x-ui.editable-field>
                            <x-ui.editable-field label="Адрес проведения" field="address" :editing="$editing === 'address'" class="sm:col-span-2">{{ $deal->address }}</x-ui.editable-field>
                            <x-ui.editable-field label="Характер мероприятия" field="event_kind" type="textarea" placeholder="Свадьба, корпоратив, день рождения…" :editing="$editing === 'event_kind'" class="sm:col-span-2">{{ $deal->event_kind }}</x-ui.editable-field>
                        </dl>
                    @elseif ($activeTab === 'notes')
                        <dl>
                            <x-ui.editable-field label="Примечания" field="notes" type="textarea" :editing="$editing === 'notes'">{{ $deal->notes }}</x-ui.editable-field>
                        </dl>
                    @elseif ($activeTab === 'history')
                        <x-activity-history :subject="$deal" :logs="$logs" :has-more="$hasMoreHistory" :expanded="$historyExpanded" created-label="Сделка создана" />
                    @else
                        <x-ui.empty-state :icon="$activeTab === 'tasks' ? 'tasks' : 'documents'" title="Появится позже"
                            :text="$activeTab === 'tasks' ? 'Задачи по сделке — в разделе «Задачи» (Фаза 6).' : 'Документы и договор — в разделе «Документы» (Фаза 7).'" />
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
