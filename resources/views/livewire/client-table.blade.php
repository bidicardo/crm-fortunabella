@php
    // Колонки, которые можно скрывать; «Имя» скрыть нельзя.
    $columns = [
        'phone' => 'Телефон',
        'email' => 'Email',
        'social' => 'Соцсеть / мессенджер',
        'legal_type' => 'Тип',
        'role' => 'Роль',
        'created_at' => 'Создан',
        'updated_at' => 'Изменён',
    ];
    $sortable = ['name', 'created_at', 'updated_at'];
@endphp

{{-- Список ровно по высоте экрана (h-main, как канбан): страница не прокручивается, таблица прокручивается
     внутри блока (flex-1 min-h-0 — до низа экрана; строк больше, чем помещается, — прокрутка внутри),
     шапка таблицы закреплена, переключатель страниц всегда виден. wire:key с номером страницы и сортировкой:
     новая страница открывается с начала таблицы, а не с прежней прокрутки блока. --}}
<div
    class="flex h-main flex-col gap-4"
    x-data="{
        cols: (() => {
            const defaults = { phone: true, email: true, social: true, legal_type: true, role: true, created_at: true, updated_at: false };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('clients.columns') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('clients.columns', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Телефон — две строки: [поиск, «Только архивные»] и [тип, «Колонки», «+ Новый»]; от sm — всё в одну строку. --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <div class="min-w-0 flex-1 sm:w-72 sm:flex-none">
                <x-ui.input type="search" icon="search" wire:model.live.debounce.300ms="search" placeholder="Поиск: имя, телефон, email…" aria-label="Поиск" />
            </div>

            <div class="shrink-0">
                <x-ui.checkbox id="only-archived" label="Только архивные" wire:model.live="onlyArchived" />
            </div>
        </div>

        {{-- flex-auto (не flex-1): группа занимает ширину содержимого и целиком уходит на новую строку, если не
             помещается (иначе на ~1100 px сжималась и кнопка вылезала за край); на новой строке растягивается. --}}
        <div class="flex flex-auto items-center gap-3">
            <div class="min-w-0 flex-1 sm:flex-initial">
                <x-ui.select wire:model.live="legalType" aria-label="Тип клиента">
                    <option value="">Все типы</option>
                    @foreach ($legalTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="relative shrink-0" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
                <x-ui.button variant="secondary" x-ref="columnsButton" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Колонки</x-ui.button>

                {{-- x-anchor (Alpine, в комплекте Livewire) сдвигает панель, чтобы на узком экране она не уходила за край --}}
                <x-ui.panel x-show="open" x-cloak x-anchor.bottom-start.offset.4="$refs.columnsButton" class="z-20 w-56">
                    <div class="px-3">
                        <x-ui.checkbox id="col-name" label="Имя" checked disabled />
                    </div>
                    @foreach ($columns as $key => $label)
                        <div class="px-3">
                            <x-ui.checkbox id="col-{{ $key }}" :label="$label" x-model="cols.{{ $key }}" x-on:change="save()" />
                        </div>
                    @endforeach
                </x-ui.panel>
            </div>

            <x-ui.button :href="route('clients.create')" icon="plus" class="shrink-0 sm:ml-auto" aria-label="Новый клиент">
                <span class="sm:hidden">Новый</span><span class="hidden sm:inline">Новый клиент</span>
            </x-ui.button>
        </div>
    </div>

    <x-ui.table class="min-h-0 flex-1 overflow-y-auto" wire:key="clients-{{ $clients->currentPage() }}-{{ $sort }}-{{ $dir }}">
        <thead class="sticky top-0 z-10 bg-surface">
            <tr>
                @foreach (['name' => 'Имя'] + $columns as $key => $label)
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
            @forelse ($clients as $client)
                <tr wire:key="client-{{ $client->id }}" class="border-t border-line">
                    {{-- Имя в одну строку не шире max-w-64, длиннее — многоточие; полностью — подсказкой --}}
                    <td class="px-4 py-2">
                        <div class="flex max-w-64 items-center gap-1">
                            <a href="{{ route('clients.show', $client) }}" title="{{ $client->name }}" class="focus-ring link min-w-0 truncate font-medium">{{ $client->name }}</a>
                            @if ($client->isArchived())
                                <x-ui.badge class="shrink-0">архив</x-ui.badge>
                            @endif
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.phone">{{ $client->phone }}</td>
                    <td class="px-4 py-2" x-show="cols.email">{{ $client->email }}</td>
                    <td class="px-4 py-2" x-show="cols.social">{{ $client->social }}</td>
                    <td class="px-4 py-2" x-show="cols.legal_type">{{ $client->legal_type?->label() }}</td>
                    <td class="px-4 py-2" x-show="cols.role">{{ $client->role }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.created_at">{{ $client->created_at->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-4 py-2" x-show="cols.updated_at">{{ $client->updated_at->format('d.m.Y') }}</td>
                </tr>
            @empty
                <tr class="border-t border-line">
                    <td colspan="{{ count($columns) + 1 }}">
                        <x-ui.empty-state icon="search" title="Клиентов не найдено" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{ $clients->links() }}
</div>
