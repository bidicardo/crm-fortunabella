@php
    $rows = [
        'Телефон' => $client->phone,
        'Email' => $client->email,
        'Соцсеть / мессенджер' => $client->social,
        'Тип клиента' => $client->legal_type?->label(),
        'Роль' => $client->role,
        'Удобное время для связи' => $client->contact_time,
    ];
@endphp

<div class="min-w-0 max-w-xl space-y-4">
    @if (session('status'))
        <p class="rounded-md border border-green-400 p-3 text-sm text-green-700 dark:text-green-400">{{ session('status') }}</p>
    @endif

    @if ($client->isArchived())
        <div class="space-y-1 rounded-md border border-amber-400 bg-amber-50 p-3 text-sm dark:bg-slate-800">
            <p>Карточка архивирована. Она доступна только для чтения.</p>
            @if ($client->mergedInto)
                <p>
                    Влит в клиента <a href="{{ route('clients.show', $client->mergedInto) }}" class="font-medium underline">{{ $client->mergedInto->name }}</a>@if ($client->mergedBy), {{ $client->mergedBy->name }}@endif, {{ $client->merged_at->format('d.m.Y H:i') }}.
                </p>
            @endif
        </div>
    @endif

    {{-- На узком экране заголовок и кнопки идут друг под другом, чтобы страница не получала горизонтальную прокрутку --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <h1 class="min-w-0 break-words text-2xl font-bold">{{ $client->name }}</h1>
        @unless ($client->isArchived())
            <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                <a href="{{ route('clients.merge', $client) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm dark:border-slate-600">Объединить с…</a>
                <a href="{{ route('clients.edit', $client) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm dark:border-slate-600">Редактировать</a>
            </div>
        @endunless
    </div>

    <dl class="space-y-3">
        @foreach ($rows as $label => $value)
            <div>
                <dt class="text-sm text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                <dd class="break-words">{{ $value ?: '—' }}</dd>
            </div>
        @endforeach

        <div>
            <dt class="text-sm text-slate-500 dark:text-slate-400">Примечания</dt>
            <dd class="whitespace-pre-line break-words">{{ $client->notes ?: '—' }}</dd>
        </div>
    </dl>

    <section class="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700">
        <h2 class="text-lg font-semibold">История изменений</h2>

        {{-- В развёрнутом виде блок прокручивается сам, а не вся страница --}}
        <div class="space-y-3 {{ $historyExpanded ? 'max-h-72 overflow-y-auto pr-2' : '' }}">
        @forelse ($logs as $log)
            <div wire:key="log-{{ $log->id }}" class="text-sm">
                <p class="text-slate-500 dark:text-slate-400">{{ $log->created_at->format('d.m.Y H:i') }} · {{ $log->user?->name ?? 'Система' }}</p>

                @if ($log->event === 'created')
                    <p>Клиент создан</p>
                @elseif ($log->event === 'updated')
                    <ul class="space-y-0.5">
                        @foreach ($log->changes ?? [] as $field => $change)
                            <li class="break-words">
                                {{ $client->activityLabel($field) }}:
                                <span class="text-slate-500 dark:text-slate-400">{{ $client->activityValue($field, $change['old'] ?? null) ?? '—' }}</span>
                                →
                                <span class="whitespace-pre-line">{{ $client->activityValue($field, $change['new'] ?? null) ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($log->event === 'merged')
                    @if (isset($log->changes['merged_client']))
                        <p>Влит клиент <a href="{{ route('clients.show', $log->changes['merged_client']['id']) }}" class="underline">{{ $log->changes['merged_client']['name'] }}</a></p>
                    @elseif (isset($log->changes['merged_into']))
                        <p>Влит в клиента <a href="{{ route('clients.show', $log->changes['merged_into']['id']) }}" class="underline">{{ $log->changes['merged_into']['name'] }}</a></p>
                    @endif
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">Записей пока нет.</p>
        @endforelse
        </div>

        @if ($hasMoreHistory)
            <button type="button" wire:click="showMoreHistory" class="text-sm underline">Показать ещё</button>
        @elseif ($historyExpanded)
            <button type="button" wire:click="collapseHistory" class="text-sm underline">Свернуть</button>
        @endif
    </section>

    <p class="text-sm text-slate-500 dark:text-slate-400">
        Создан {{ $client->created_at->format('d.m.Y H:i') }} · изменён {{ $client->updated_at->format('d.m.Y H:i') }}
    </p>
</div>
