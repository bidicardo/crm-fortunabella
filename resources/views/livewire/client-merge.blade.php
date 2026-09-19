@php
    use App\Livewire\ClientMerge;
    use App\Services\ClientMergeService;

    $input = 'w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-600';
    $value = fn ($c, $f) => ClientMergeService::value($c, $f);
    // legal_type хранится значением enum, для показа берём подпись.
    $show = fn ($c, $f) => $f === 'legal_type' ? $c->legal_type?->label() : $value($c, $f);
@endphp

<div class="max-w-4xl space-y-4">
    <p class="text-sm text-slate-600 dark:text-slate-300">
        Открытая карточка: <a href="{{ route('clients.show', $client) }}" class="font-medium underline">{{ $client->name }}</a>
    </p>

    @error('merge') <p class="rounded-md border border-red-300 p-3 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p> @enderror

    @if (! $other)
        <div class="max-w-xl space-y-3">
            <label for="search" class="block text-sm">С каким клиентом объединить? Поиск по имени, телефону или email</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search" class="{{ $input }}" autofocus>

            <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse ($candidates as $candidate)
                    <li wire:key="candidate-{{ $candidate->id }}">
                        <button type="button" wire:click="pick({{ $candidate->id }})" class="block min-h-11 w-full py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-800">
                            <span class="block font-medium">{{ $candidate->name }}</span>
                            <span class="block text-sm text-slate-500 dark:text-slate-400">{{ collect([$candidate->phone, $candidate->email])->filter()->implode(' · ') }}</span>
                        </button>
                    </li>
                @empty
                    <li class="py-3 text-slate-500">Клиентов не найдено</li>
                @endforelse
            </ul>
        </div>
    @else
        <fieldset class="space-y-2">
            <legend class="text-sm font-semibold">Какая карточка основная</legend>
            <p class="text-sm text-slate-500 dark:text-slate-400">Вторая карточка будет перенесена в архив, её данные останутся нетронутыми.</p>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="radio" wire:model.live="mainSide" value="current"> {{ $client->name }}</label>
                <label class="flex items-center gap-2"><input type="radio" wire:model.live="mainSide" value="other"> {{ $other->name }}</label>
            </div>
        </fieldset>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="py-2 pr-4">Поле</th>
                        <th class="py-2 pr-4">{{ $client->name }} {{ $mainSide === 'current' ? '(основная)' : '(дубль)' }}</th>
                        <th class="py-2 pr-4">{{ $other->name }} {{ $mainSide === 'other' ? '(основная)' : '(дубль)' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (ClientMerge::LABELS as $field => $label)
                        @php $differs = in_array($field, $differing, true); @endphp
                        <tr class="border-b border-slate-100 align-top dark:border-slate-800">
                            <td class="py-2 pr-4 text-slate-500 dark:text-slate-400">{{ $label }}</td>
                            @if ($differs)
                                <td class="py-2 pr-4">
                                    <label class="flex items-start gap-2">
                                        <input type="radio" wire:model="choices.{{ $field }}" value="current" class="mt-1">
                                        <span class="whitespace-pre-line break-words">{{ $show($client, $field) ?? '—' }}</span>
                                    </label>
                                </td>
                                <td class="py-2 pr-4">
                                    <label class="flex items-start gap-2">
                                        <input type="radio" wire:model="choices.{{ $field }}" value="other" class="mt-1">
                                        <span class="whitespace-pre-line break-words">{{ $show($other, $field) ?? '—' }}</span>
                                    </label>
                                    @if ($field === 'notes')
                                        <label class="mt-2 flex items-center gap-2">
                                            <input type="radio" wire:model="choices.notes" value="both"> Объединить оба текста
                                        </label>
                                    @endif
                                </td>
                            @else
                                <td class="py-2 pr-4 whitespace-pre-line break-words" colspan="2">{{ $show($client, $field) ?? '—' }} <span class="text-xs text-slate-400">(совпадает)</span></td>
                            @endif
                        </tr>
                    @endforeach
                    {{-- Связанные данные: сейчас их нет; в Фазах 4, 6, 7 счётчики берутся из связей и переносятся при слиянии. --}}
                    @foreach (['Сделки', 'Задачи', 'Документы'] as $related)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2 pr-4 text-slate-500 dark:text-slate-400">{{ $related }}</td>
                            <td class="py-2 pr-4">0</td>
                            <td class="py-2 pr-4">0</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" wire:click="merge"
                wire:confirm="Объединить клиентов? Вторая карточка будет перенесена в архив. Это действие нельзя отменить."
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500">Объединить</button>
            <button type="button" wire:click="back" class="text-sm underline">Выбрать другого клиента</button>
        </div>
    @endif
</div>
