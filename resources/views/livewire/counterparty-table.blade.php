@php
    // Колонки, которые можно скрывать; «Название» скрыть нельзя. У действующих этап один — колонки этапа нет.
    $columns = array_filter([
        'type' => 'Тип',
        'stage' => $active ? null : 'Этап',
        'phone' => 'Телефон',
        'email' => 'Email',
        'contact' => 'Основной контакт',
        'cooperation_started_at' => 'Начало сотрудничества',
        'created_at' => 'Создан',
        'updated_at' => 'Изменён',
    ]);
    $sortable = ['name', 'cooperation_started_at', 'created_at', 'updated_at'];
    $storageKey = $active ? 'counterparties.active.columns' : 'counterparties.columns';
@endphp

{{-- Список ровно по высоте экрана, как client-table (h-main): таблица до низа экрана (flex-1) и
     прокручивается внутри блока со sticky-шапкой, переключатель страниц всегда виден; wire:key — новая
     страница/сортировка с начала таблицы. --}}
<div
    class="flex h-main flex-col gap-4"
    x-data="{
        cols: (() => {
            const defaults = { type: true, stage: true, phone: true, email: true, contact: true, cooperation_started_at: true, created_at: true, updated_at: false };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('{{ $storageKey }}') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('{{ $storageKey }}', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Телефон — две строки: [вид | поиск] и [этап, «Колонки», «+ Новый»]; от sm — всё в одну строку. --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex w-full items-center gap-3 sm:w-auto">
            @unless ($active)
                <div class="inline-flex shrink-0 rounded-md border border-line p-0.5" role="group" aria-label="Вид">
                    <a href="{{ route('counterparties.index') }}" class="focus-ring inline-flex min-h-10 items-center rounded-sm px-3 hover:bg-line/50">Канбан</a>
                    <span class="inline-flex min-h-10 items-center rounded-sm bg-accent-fill px-3 font-semibold text-on-accent" aria-current="page">Список</span>
                </div>
            @endunless

            <div class="min-w-0 flex-1 sm:w-72 sm:flex-none">
                <x-ui.input type="search" icon="search" wire:model.live.debounce.300ms="search" placeholder="Поиск: название, телефон, контакт…" aria-label="Поиск" />
            </div>
        </div>

        {{-- flex-auto (не flex-1): группа занимает ширину содержимого и целиком уходит на новую строку, если не
             помещается (иначе на ~1100 px сжималась и кнопка вылезала за край); на новой строке растягивается. --}}
        <div class="flex flex-auto items-center gap-3">
            @unless ($active)
                <div class="min-w-0 flex-1 sm:flex-initial">
                    <x-ui.select wire:model.live="stage" aria-label="Этап">
                        <option value="">Все этапы</option>
                        @foreach ($stages as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            @endunless

            <div class="relative shrink-0" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
                <x-ui.button variant="secondary" x-ref="columnsButton" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Колонки</x-ui.button>

                {{-- z-20: над закреплённой шапкой таблицы (z-10); x-anchor не даёт панели уйти за край на телефоне --}}
                <x-ui.panel x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.columnsButton" class="z-20 w-56">
                    <div class="px-3">
                        <x-ui.checkbox id="col-name" label="Название" checked disabled />
                    </div>
                    @foreach ($columns as $key => $label)
                        <div class="px-3">
                            <x-ui.checkbox id="col-{{ $key }}" :label="$label" x-model="cols.{{ $key }}" x-on:change="save()" />
                        </div>
                    @endforeach
                </x-ui.panel>
            </div>

            <x-ui.button :href="route('counterparties.create')" icon="plus" class="shrink-0 sm:ml-auto" aria-label="Новый контрагент">
                <span class="sm:hidden">Новый</span><span class="hidden sm:inline">Новый контрагент</span>
            </x-ui.button>
        </div>
    </div>

    <x-ui.table class="min-h-0 flex-1 overflow-y-auto" wire:key="counterparties-{{ $counterparties->currentPage() }}-{{ $sort }}-{{ $dir }}">
        <thead class="sticky top-0 z-10 bg-surface">
            <tr>
                @foreach (['name' => 'Название'] + $columns as $key => $label)
                    <th
                        class="whitespace-nowrap px-4 py-2 text-caption font-semibold"
                        @if ($key !== 'name') x-show="cols.{{ $key }}" @endif
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
            @forelse ($counterparties as $counterparty)
                <tr wire:key="counterparty-{{ $counterparty->id }}" class="border-t border-line">
                    {{-- Название в одну строку не шире max-w-64, длиннее — многоточие; полностью — подсказкой --}}
                    <td class="px-4 py-2">
                        <a href="{{ route('counterparties.show', $counterparty) }}" title="{{ $counterparty->name }}" class="focus-ring link block max-w-64 truncate font-medium">{{ $counterparty->name }}</a>
                    </td>
                    <td class="px-4 py-2" x-show="cols.type">{{ $counterparty->type }}</td>
                    @unless ($active)
                        <td class="whitespace-nowrap px-4 py-2" x-show="cols.stage">
                            <x-ui.badge :tone="$counterparty->stage->tone()">{{ $counterparty->stage->label() }}</x-ui.badge>
                        </td>
                    @endunless
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.phone">{{ $counterparty->phone }}</td>
                    <td class="px-4 py-2" x-show="cols.email">{{ $counterparty->email }}</td>
                    <td class="px-4 py-2" x-show="cols.contact">{{ $counterparty->contacts->first()?->full_name }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.cooperation_started_at">{{ $counterparty->cooperation_started_at?->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.created_at">{{ $counterparty->created_at->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.updated_at">{{ $counterparty->updated_at->format('d.m.Y') }}</td>
                </tr>
            @empty
                <tr class="border-t border-line">
                    <td colspan="{{ count($columns) + 1 }}">
                        <x-ui.empty-state icon="search" title="Контрагентов не найдено" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{ $counterparties->links() }}
</div>
