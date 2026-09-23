<div class="min-w-0 max-w-xl space-y-4">
    @if (session('status'))
        <x-ui.alert kind="success">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($client->isArchived())
        <x-ui.alert kind="warning">
            <div class="space-y-1">
                <p>Карточка архивирована. Она доступна только для чтения.</p>
                @if ($client->mergedInto)
                    <p>
                        Влит в клиента <a href="{{ route('clients.show', $client->mergedInto) }}" class="focus-ring link font-medium">{{ $client->mergedInto->name }}</a>@if ($client->mergedBy), {{ $client->mergedBy->name }}@endif, {{ $client->merged_at->format('d.m.Y H:i') }}.
                    </p>
                @endif
            </div>
        </x-ui.alert>
    @endif

    {{-- На узком экране заголовок и кнопки идут друг под другом, чтобы страница не получала горизонтальную прокрутку --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <h1 class="min-w-0 break-words text-h3 font-bold">{{ $client->name }}</h1>
        @unless ($client->isArchived())
            <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                <x-ui.button variant="secondary" icon="merge" :href="route('clients.merge', $client)">Объединить с…</x-ui.button>
                <x-ui.button variant="secondary" icon="edit" :href="route('clients.edit', $client)">Редактировать</x-ui.button>
            </div>
        @endunless
    </div>

    {{-- Поля в рамках и в том же порядке, что и в форме редактирования (client-form) --}}
    <dl class="grid gap-4 sm:grid-cols-2">
        <x-ui.field label="Телефон" class="sm:col-span-2">{{ $client->phone }}</x-ui.field>
        <x-ui.field label="Email" class="sm:col-span-2">{{ $client->email }}</x-ui.field>
        <x-ui.field label="Соцсеть / мессенджер" class="sm:col-span-2">{{ $client->social }}</x-ui.field>
        <x-ui.field label="Тип клиента">{{ $client->legal_type?->label() }}</x-ui.field>
        <x-ui.field label="Роль">{{ $client->role }}</x-ui.field>
        <x-ui.field label="Удобное время для связи" class="sm:col-span-2">{{ $client->contact_time }}</x-ui.field>
        <x-ui.field label="Примечания" class="sm:col-span-2" multiline>{{ $client->notes }}</x-ui.field>
    </dl>

    <section class="space-y-3 border-t border-line pt-4">
        <h2 class="text-h4 font-semibold">История изменений</h2>

        {{-- В развёрнутом виде блок прокручивается сам, а не вся страница --}}
        <div class="space-y-3 {{ $historyExpanded ? 'max-h-72 overflow-y-auto pr-2' : '' }}">
        @forelse ($logs as $log)
            <div wire:key="log-{{ $log->id }}">
                <p class="text-caption text-ink-secondary">{{ $log->created_at->format('d.m.Y H:i') }} · {{ $log->user?->name ?? 'Система' }}</p>

                @if ($log->event === 'created')
                    <p>Клиент создан</p>
                @elseif ($log->event === 'updated')
                    <ul class="space-y-0.5">
                        @foreach ($log->changes ?? [] as $field => $change)
                            <li class="break-words">
                                {{ $client->activityLabel($field) }}:
                                <span class="text-ink-secondary">{{ $client->activityValue($field, $change['old'] ?? null) ?? '—' }}</span>
                                →
                                <span class="whitespace-pre-line">{{ $client->activityValue($field, $change['new'] ?? null) ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($log->event === 'merged')
                    @if (isset($log->changes['merged_client']))
                        <p>Влит клиент <a href="{{ route('clients.show', $log->changes['merged_client']['id']) }}" class="focus-ring link">{{ $log->changes['merged_client']['name'] }}</a></p>
                    @elseif (isset($log->changes['merged_into']))
                        <p>Влит в клиента <a href="{{ route('clients.show', $log->changes['merged_into']['id']) }}" class="focus-ring link">{{ $log->changes['merged_into']['name'] }}</a></p>
                    @endif
                @endif
            </div>
        @empty
            <p class="text-ink-secondary">Записей пока нет.</p>
        @endforelse
        </div>

        @if ($hasMoreHistory)
            <x-ui.button variant="text" wire:click="showMoreHistory">Показать ещё</x-ui.button>
        @elseif ($historyExpanded)
            <x-ui.button variant="text" wire:click="collapseHistory">Свернуть</x-ui.button>
        @endif
    </section>

    <p class="text-caption text-ink-secondary">
        Создан {{ $client->created_at->format('d.m.Y H:i') }} · изменён {{ $client->updated_at->format('d.m.Y H:i') }}
    </p>
</div>
