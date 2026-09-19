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
    $input = 'rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-600';
    $sortable = ['name', 'created_at', 'updated_at'];
@endphp

<div
    class="space-y-4"
    x-data="{
        cols: (() => {
            const defaults = { phone: true, email: true, social: true, legal_type: true, role: true, created_at: true, updated_at: false };
            try { return { ...defaults, ...JSON.parse(localStorage.getItem('clients.columns') || '{}') }; } catch (e) { return defaults; }
        })(),
        open: false,
        save() { try { localStorage.setItem('clients.columns', JSON.stringify(this.cols)); } catch (e) {} },
    }"
>
    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Поиск: имя, телефон, email…" aria-label="Поиск"
            class="{{ $input }} w-full sm:w-72">

        <select wire:model.live="legalType" aria-label="Тип клиента" class="{{ $input }} dark:bg-slate-900">
            <option value="">Все типы</option>
            @foreach ($legalTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="showArchived"> Показывать архивные
        </label>

        <div class="relative" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
            <button type="button" class="{{ $input }}" aria-haspopup="true" x-bind:aria-expanded="open" x-on:click="open = !open">Колонки</button>
            <div x-show="open" x-cloak class="absolute left-0 z-10 mt-1 w-56 space-y-1 rounded-md border border-slate-200 bg-white p-3 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" checked disabled> Имя</label>
                @foreach ($columns as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="cols.{{ $key }}" x-on:change="save()"> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <a href="{{ route('clients.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500 sm:ml-auto">Новый клиент</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-700">
                    @foreach (['name' => 'Имя'] + $columns as $key => $label)
                        <th
                            class="whitespace-nowrap py-2 pr-4"
                            @if ($key !== 'name') x-show="cols.{{ $key }}" @endif
                            @if (in_array($key, $sortable)) aria-sort="{{ $sort === $key ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif
                        >
                            @if (in_array($key, $sortable))
                                <button type="button" wire:click="sortBy('{{ $key }}')" class="font-semibold hover:underline">
                                    {{ $label }}@if ($sort === $key) {{ $dir === 'asc' ? '▲' : '▼' }}@endif
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
                    <tr wire:key="client-{{ $client->id }}" class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2 pr-4">
                            <a href="{{ route('clients.show', $client) }}" class="font-medium underline">{{ $client->name }}</a>
                            @if ($client->isArchived())
                                <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-xs dark:bg-slate-700">архив</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-2 pr-4" x-show="cols.phone">{{ $client->phone }}</td>
                        <td class="py-2 pr-4" x-show="cols.email">{{ $client->email }}</td>
                        <td class="py-2 pr-4" x-show="cols.social">{{ $client->social }}</td>
                        <td class="py-2 pr-4" x-show="cols.legal_type">{{ $client->legal_type?->label() }}</td>
                        <td class="py-2 pr-4" x-show="cols.role">{{ $client->role }}</td>
                        <td class="whitespace-nowrap py-2 pr-4" x-show="cols.created_at">{{ $client->created_at->format('d.m.Y') }}</td>
                        <td class="whitespace-nowrap py-2 pr-4" x-show="cols.updated_at">{{ $client->updated_at->format('d.m.Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}" class="py-4 text-slate-500">Клиентов не найдено</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $clients->links() }}
</div>
