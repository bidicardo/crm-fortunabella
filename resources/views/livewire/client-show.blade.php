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

<div class="max-w-xl space-y-4">
    @if (session('status'))
        <p class="rounded-md border border-green-400 p-3 text-sm text-green-700 dark:text-green-400">{{ session('status') }}</p>
    @endif

    @if ($client->isArchived())
        <p class="rounded-md border border-amber-400 bg-amber-50 p-3 text-sm dark:bg-slate-800">Карточка архивирована. Она доступна только для чтения.</p>
    @endif

    <div class="flex items-start justify-between gap-3">
        <h1 class="break-words text-2xl font-bold">{{ $client->name }}</h1>
        @unless ($client->isArchived())
            <a href="{{ route('clients.edit', $client) }}" class="shrink-0 rounded-md border border-slate-300 px-3 py-1.5 text-sm dark:border-slate-600">Редактировать</a>
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

    <p class="text-sm text-slate-500 dark:text-slate-400">
        Создан {{ $client->created_at->format('d.m.Y H:i') }} · изменён {{ $client->updated_at->format('d.m.Y H:i') }}
    </p>
</div>
