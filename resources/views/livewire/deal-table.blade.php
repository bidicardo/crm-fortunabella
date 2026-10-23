@php
    // Колонки, которые можно скрывать; «Название» скрыть нельзя.
    $columns = [
        'client' => 'Клиент',
        'counterparty' => 'Контрагент',
        'stage' => 'Этап',
        'event_date' => 'Дата проведения',
        'amount' => 'Сумма',
        'prepayment' => 'Предоплата',
        'source' => 'Источник',
        'seller' => 'Кто продал',
        'created_at' => 'Создана',
        'updated_at' => 'Изменена',
    ];
    $sortable = ['title', 'event_date', 'amount', 'created_at', 'updated_at'];
@endphp

{{-- Список сделок (задача 28а) — по образцу counterparty-table: ровно по высоте экрана (h-main), таблица до низа
     экрана и прокручивается внутри блока со sticky-шапкой, переключатель страниц всегда виден; флажки строк —
     массовое удаление (DeletesSelected). Телефон: две строки над таблицей, фильтр источника — от sm. --}}
<div
    class="flex h-main flex-col gap-4"
    x-data="{
        cols: (() => {
            const defaults = { client: true, counterparty: true, stage: true, event_date: true, amount: true, prepayment: true, source: true, seller: true, created_at: false, updated_at: false };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('deals.columns') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('deals.columns', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    @include('livewire.partials.bulk-bar', ['confirm' => 'Удалить выбранные сделки'])

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <div class="inline-flex shrink-0 rounded-md border border-line p-0.5" role="group" aria-label="Вид">
                <a href="{{ route('deals.index') }}" class="focus-ring inline-flex min-h-10 items-center rounded-sm px-3 hover:bg-line/50">Канбан</a>
                <span class="inline-flex min-h-10 items-center rounded-sm bg-accent-fill px-3 font-semibold text-on-accent" aria-current="page">Список</span>
            </div>

            <div class="min-w-0 flex-1 sm:w-72 sm:flex-none">
                <x-ui.input type="search" icon="search" wire:model.live.debounce.300ms="search" placeholder="Поиск: название, клиент, телефон…" aria-label="Поиск" />
            </div>
        </div>

        {{-- flex-auto: группа целиком уходит на новую строку, если не помещается --}}
        <div class="flex flex-auto items-center gap-3">
            <div class="min-w-0 flex-1 sm:flex-initial">
                <x-ui.select wire:model.live="stage" aria-label="Этап">
                    <option value="">Все этапы</option>
                    @foreach ($stages as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="min-w-0 max-sm:hidden sm:flex-initial">
                <x-ui.select wire:model.live="source" aria-label="Источник лида">
                    <option value="">Все источники</option>
                    @foreach ($sources as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="relative shrink-0" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
                <x-ui.button variant="secondary" x-ref="columnsButton" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Колонки</x-ui.button>

                {{-- z-20: над закреплённой шапкой таблицы (z-10); x-anchor не даёт панели уйти за край на телефоне --}}
                <x-ui.panel x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.columnsButton" class="z-20 w-56">
                    <div class="px-3">
                        <x-ui.checkbox id="col-title" label="Название" checked disabled />
                    </div>
                    @foreach ($columns as $key => $label)
                        <div class="px-3">
                            <x-ui.checkbox id="col-{{ $key }}" :label="$label" x-model="cols.{{ $key }}" x-on:change="save()" />
                        </div>
                    @endforeach
                </x-ui.panel>
            </div>

            <x-ui.button :href="route('deals.create')" icon="plus" class="shrink-0 sm:ml-auto" aria-label="Новая сделка">
                <span class="sm:hidden">Новая</span><span class="hidden sm:inline">Новая сделка</span>
            </x-ui.button>
        </div>
    </div>

    <x-ui.table class="min-h-0 flex-1 overflow-y-auto" wire:key="deals-{{ $deals->currentPage() }}-{{ $sort }}-{{ $dir }}">
        <thead class="sticky top-0 z-10 bg-surface">
            <tr>
                @php($pageIds = $deals->pluck('id')->map(fn ($id) => (string) $id)->all())
                <th class="w-10 pl-3">
                    <x-ui.checkbox id="select-page" :checked="$pageIds && ! array_diff($pageIds, $selected)" wire:click="togglePage"><span class="sr-only">Выбрать все на странице</span></x-ui.checkbox>
                </th>
                @foreach (['title' => 'Название'] + $columns as $key => $label)
                    <th
                        class="whitespace-nowrap px-4 py-2 text-caption font-semibold"
                        @if ($key !== 'title') x-show="cols.{{ $key }}" @endif
                        @if (in_array($key, $sortable)) aria-sort="{{ $sort === $key ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif
                    >
                        @if (in_array($key, $sortable))
                            <button type="button" wire:click="sortBy('{{ $key }}')" class="focus-ring inline-flex items-center gap-1 rounded-sm font-semibold hover:underline">
                                {{ $label }}
                                @if ($sort === $key)
                                    <x-icon :name="$dir === 'asc' ? 'sort-up' : 'sort-down'" class="h-3.5 w-3.5" />
                                @endif
                            </button>
                        @else
                            {{ $label }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($deals as $deal)
                <tr wire:key="deal-{{ $deal->id }}" class="border-t border-line">
                    <td class="w-10 pl-3">
                        <x-ui.checkbox id="select-{{ $deal->id }}" value="{{ $deal->id }}" :checked="in_array((string) $deal->id, $selected, true)" wire:model.live="selected"><span class="sr-only">Выбрать {{ $deal->title }}</span></x-ui.checkbox>
                    </td>
                    {{-- Название в одну строку не шире max-w-64, длиннее — многоточие; полностью — подсказкой --}}
                    <td class="px-4 py-2">
                        <a href="{{ route('deals.show', $deal) }}" title="{{ $deal->title }}" class="focus-ring link block max-w-64 truncate font-medium">{{ $deal->title }}</a>
                    </td>
                    <td class="px-4 py-2" x-show="cols.client"><a href="{{ route('clients.show', $deal->client_id) }}" class="focus-ring link">{{ $deal->client->name }}</a></td>
                    <td class="px-4 py-2" x-show="cols.counterparty">{{ $deal->counterparty?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.stage"><x-ui.badge :tone="$deal->stage->tone()">{{ $deal->stage->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap px-4 py-2 tabular-nums" x-show="cols.event_date">{{ $deal->event_date ? trim($deal->event_date->format('d.m.Y').' '.$deal->event_time) : '' }}</td>
                    <td class="whitespace-nowrap px-4 py-2 tabular-nums" x-show="cols.amount">{{ App\Models\Deal::money($deal->amount) }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.prepayment">
                        @if ($deal->prepayment_amount !== null || $deal->prepayment_paid)
                            <span class="tabular-nums">{{ App\Models\Deal::money($deal->prepayment_amount) }}</span>
                            <x-ui.badge :tone="$deal->prepayment_paid ? 'success' : 'warning'">{{ $deal->prepayment_paid ? 'получена' : 'не получена' }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-4 py-2" x-show="cols.source">{{ $deal->lead_source === App\Enums\LeadSource::Other && $deal->lead_source_other ? $deal->lead_source_other : $deal->lead_source?->label() }}</td>
                    <td class="px-4 py-2" x-show="cols.seller">{{ $deal->soldBy?->name }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.created_at">{{ $deal->created_at->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.updated_at">{{ $deal->updated_at->format('d.m.Y') }}</td>
                </tr>
            @empty
                <tr class="border-t border-line">
                    <td colspan="{{ count($columns) + 2 }}">
                        <x-ui.empty-state icon="search" title="Сделок не найдено" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{ $deals->links() }}
</div>
