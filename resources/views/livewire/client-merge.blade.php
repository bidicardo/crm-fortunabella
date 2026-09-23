@php
    use App\Livewire\ClientMerge;
    use App\Services\ClientMergeService;

    $value = fn ($c, $f) => ClientMergeService::value($c, $f);
    // legal_type хранится значением enum, для показа берём подпись.
    $show = fn ($c, $f) => $f === 'legal_type' ? $c->legal_type?->label() : $value($c, $f);
    $th = 'px-4 py-2 text-caption font-semibold';
    $td = 'px-4 py-2';
@endphp

<div class="max-w-4xl space-y-4">
    <p class="text-ink-secondary">
        Открытая карточка: <a href="{{ route('clients.show', $client) }}" class="focus-ring link font-medium">{{ $client->name }}</a>
    </p>

    @error('merge') <x-ui.alert kind="error">{{ $message }}</x-ui.alert> @enderror

    @if (! $other)
        <div class="max-w-xl space-y-3">
            <x-ui.input id="search" label="С каким клиентом объединить? Поиск по имени, телефону или email" type="search" icon="search"
                wire:model.live.debounce.300ms="search" autofocus />

            <ul class="divide-y divide-line">
                @forelse ($candidates as $candidate)
                    <li wire:key="candidate-{{ $candidate->id }}">
                        <button type="button" wire:click="pick({{ $candidate->id }})" class="focus-ring block min-h-11 w-full rounded-md px-2 py-2 text-left hover:bg-line/50">
                            <span class="block font-medium">{{ $candidate->name }}</span>
                            <span class="block text-caption text-ink-secondary">{{ collect([$candidate->phone, $candidate->email])->filter()->implode(' · ') }}</span>
                        </button>
                    </li>
                @empty
                    <li>
                        <x-ui.empty-state icon="search" title="Клиентов не найдено" />
                    </li>
                @endforelse
            </ul>
        </div>
    @else
        <fieldset class="space-y-2">
            <legend class="font-semibold">Какая карточка основная</legend>
            <p class="text-caption text-ink-secondary">Вторая карточка будет перенесена в архив, её данные останутся нетронутыми.</p>
            <div class="flex flex-wrap gap-x-4">
                <x-ui.radio name="mainSide" value="current" wire:model.live="mainSide" :label="$client->name" />
                <x-ui.radio name="mainSide" value="other" wire:model.live="mainSide" :label="$other->name" />
            </div>
        </fieldset>

        <x-ui.table>
            <thead class="bg-surface">
                <tr>
                    <th class="{{ $th }}">Поле</th>
                    <th class="{{ $th }}">{{ $client->name }} {{ $mainSide === 'current' ? '(основная)' : '(дубль)' }}</th>
                    <th class="{{ $th }}">{{ $other->name }} {{ $mainSide === 'other' ? '(основная)' : '(дубль)' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach (ClientMerge::LABELS as $field => $label)
                    @php $differs = in_array($field, $differing, true); @endphp
                    <tr class="border-t border-line align-top">
                        <td class="{{ $td }} text-ink-secondary">{{ $label }}</td>
                        @if ($differs)
                            <td class="{{ $td }}">
                                <x-ui.radio name="choice-{{ $field }}" value="current" wire:model="choices.{{ $field }}">
                                    <span class="whitespace-pre-line">{{ $show($client, $field) ?? '—' }}</span>
                                </x-ui.radio>
                            </td>
                            <td class="{{ $td }}">
                                <x-ui.radio name="choice-{{ $field }}" value="other" wire:model="choices.{{ $field }}">
                                    <span class="whitespace-pre-line">{{ $show($other, $field) ?? '—' }}</span>
                                </x-ui.radio>
                                @if ($field === 'notes')
                                    <x-ui.radio name="choice-{{ $field }}" value="both" wire:model="choices.notes" label="Объединить оба текста" />
                                @endif
                            </td>
                        @else
                            <td class="{{ $td }} whitespace-pre-line break-words" colspan="2">{{ $show($client, $field) ?? '—' }} <span class="text-caption text-ink-muted">(совпадает)</span></td>
                        @endif
                    </tr>
                @endforeach
                {{-- Связанные данные: сейчас их нет; в Фазах 4, 6, 7 счётчики берутся из связей и переносятся при слиянии. --}}
                @foreach (['Сделки', 'Задачи', 'Документы'] as $related)
                    <tr class="border-t border-line">
                        <td class="{{ $td }} text-ink-secondary">{{ $related }}</td>
                        <td class="{{ $td }}">0</td>
                        <td class="{{ $td }}">0</td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button wire:click="merge"
                wire:confirm="Объединить клиентов? Вторая карточка будет перенесена в архив. Это действие нельзя отменить.">Объединить</x-ui.button>
            <x-ui.button variant="text" wire:click="back">Выбрать другого клиента</x-ui.button>
        </div>
    @endif
</div>
