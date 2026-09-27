@props([
    'subject',
    'logs',
    'hasMore' => false,
    'expanded' => false,
    'createdLabel' => 'Запись создана',
])

{{-- Блок «История изменений» карточки (клиент, контрагент). Состояние и запрос — трейт
     App\Livewire\Concerns\WithActivityHistory (методы showMoreHistory, collapseHistory). --}}
@php
    $contactLabels = (new App\Models\CounterpartyContact)->activityLabels();
@endphp

<x-ui.card title="История изменений">
    <div class="space-y-3">
        {{-- В развёрнутом виде блок прокручивается сам, а не вся страница --}}
        <div class="space-y-3 {{ $expanded ? 'max-h-72 overflow-y-auto pr-2' : '' }}">
            @forelse ($logs as $log)
                <div wire:key="log-{{ $log->id }}">
                    <p class="text-caption text-ink-secondary">{{ $log->created_at->format('d.m.Y H:i') }} · {{ $log->user?->name ?? 'Система' }}</p>

                    @switch ($log->event)
                        @case ('created')
                            <p>{{ $createdLabel }}</p>
                            @break

                        @case ('updated')
                            <ul class="space-y-0.5">
                                @foreach ($log->changes ?? [] as $field => $change)
                                    <li class="break-words">
                                        {{ $subject->activityLabel($field) }}:
                                        <span class="text-ink-secondary">{{ $subject->activityValue($field, $change['old'] ?? null) ?? '—' }}</span>
                                        →
                                        <span class="whitespace-pre-line">{{ $subject->activityValue($field, $change['new'] ?? null) ?? '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @break

                        @case ('merged')
                            @if (isset($log->changes['merged_client']))
                                <p class="break-words">Влит клиент <a href="{{ route('clients.show', $log->changes['merged_client']['id']) }}" class="focus-ring link">{{ $log->changes['merged_client']['name'] }}</a></p>
                            @elseif (isset($log->changes['merged_into']))
                                <p class="break-words">Влит в клиента <a href="{{ route('clients.show', $log->changes['merged_into']['id']) }}" class="focus-ring link">{{ $log->changes['merged_into']['name'] }}</a></p>
                            @endif
                            @break

                        @case ('contact_added')
                            <p class="break-words">Добавлено контактное лицо: {{ $log->changes['contact'] ?? '' }}</p>
                            @break

                        @case ('contact_updated')
                            <p class="break-words">Контактное лицо {{ $log->changes['contact'] ?? '' }}:</p>
                            <ul class="space-y-0.5">
                                @foreach ($log->changes['fields'] ?? [] as $field => $change)
                                    <li class="break-words">
                                        {{ $contactLabels[$field] ?? $field }}:
                                        <span class="text-ink-secondary">{{ filled($change['old'] ?? null) ? $change['old'] : '—' }}</span>
                                        →
                                        <span class="whitespace-pre-line">{{ filled($change['new'] ?? null) ? $change['new'] : '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @break

                        @case ('contact_removed')
                            <p class="break-words">Удалено контактное лицо: {{ $log->changes['contact'] ?? '' }}</p>
                            @break
                    @endswitch
                </div>
            @empty
                <p class="text-ink-secondary">Записей пока нет.</p>
            @endforelse
        </div>

        @if ($hasMore)
            <x-ui.button variant="text" wire:click="showMoreHistory">Показать ещё</x-ui.button>
        @elseif ($expanded)
            <x-ui.button variant="text" wire:click="collapseHistory">Свернуть</x-ui.button>
        @endif
    </div>
</x-ui.card>
