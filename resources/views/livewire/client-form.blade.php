@php
    $input = 'w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600';
    $error = 'mt-1 text-sm text-red-600 dark:text-red-400';
@endphp

<form wire:submit="save" class="max-w-xl space-y-4" x-data x-on:duplicates-found.window="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
    @if ($duplicateClients->isNotEmpty())
        <div class="space-y-3 rounded-md border border-amber-400 bg-amber-50 p-3 dark:bg-slate-800" role="alert">
            <p class="text-sm font-semibold">Возможно, такой клиент уже есть</p>

            @foreach ($duplicateClients as $duplicate)
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-amber-200 pt-2 text-sm dark:border-slate-700">
                    <div class="min-w-0">
                        <p class="break-words font-medium">{{ $duplicate->name }}</p>
                        <p class="break-words text-slate-600 dark:text-slate-300">{{ collect([$duplicate->phone, $duplicate->email])->filter()->implode(' · ') }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Совпало: {{ collect($duplicates[$duplicate->id] ?? [])->map(fn ($f) => $f === 'phone' ? 'телефон' : 'email')->implode(', ') }}
                        </p>
                    </div>
                    <a href="{{ route('clients.show', $duplicate) }}" class="shrink-0 rounded-md border border-slate-300 px-3 py-1.5 dark:border-slate-600">Открыть</a>
                </div>
            @endforeach

            <button type="button" wire:click="save(true)" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500">Продолжить создание</button>
        </div>
    @endif

    <div>
        <label for="name" class="mb-1 block text-sm">Имя *</label>
        <input id="name" type="text" wire:model="name" required class="{{ $input }}">
        @error('name') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="phone" class="mb-1 block text-sm">Телефон</label>
        <input id="phone" type="tel" wire:model="phone" placeholder="+7 917 123-45-67" class="{{ $input }}">
        @error('phone') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm">Email</label>
        <input id="email" type="email" wire:model="email" class="{{ $input }}">
        @error('email') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="social" class="mb-1 block text-sm">Соцсеть или мессенджер</label>
        <input id="social" type="text" wire:model="social" class="{{ $input }}">
        @error('social') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="legal_type" class="mb-1 block text-sm">Тип клиента</label>
            <select id="legal_type" wire:model="legal_type" class="{{ $input }} dark:bg-slate-900">
                <option value="">—</option>
                @foreach ($legalTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('legal_type') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm">Роль</label>
            <input id="role" type="text" wire:model="role" placeholder="Например: невеста, организатор" class="{{ $input }}">
            @error('role') <p class="{{ $error }}">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="contact_time" class="mb-1 block text-sm">Удобное время для связи</label>
        <input id="contact_time" type="text" wire:model="contact_time" class="{{ $input }}">
        @error('contact_time') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="notes" class="mb-1 block text-sm">Примечания</label>
        <textarea id="notes" rows="4" wire:model="notes" class="{{ $input }}"></textarea>
        @error('notes') <p class="{{ $error }}">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">Сохранить</button>
        @if ($client)
            <a href="{{ route('clients.show', $client) }}" class="text-sm underline">Отмена</a>
        @endif
    </div>
</form>
