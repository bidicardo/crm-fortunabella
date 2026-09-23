<form wire:submit="save" class="max-w-xl space-y-4" x-data x-on:duplicates-found.window="$nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))">
    @if ($duplicateClients->isNotEmpty())
        <x-ui.alert kind="warning" title="Возможно, такой клиент уже есть">
            <div class="space-y-3">
                @foreach ($duplicateClients as $duplicate)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-2">
                        <div class="min-w-0">
                            <p class="break-words font-medium">{{ $duplicate->name }}</p>
                            <p class="break-words text-ink-secondary">{{ collect([$duplicate->phone, $duplicate->email])->filter()->implode(' · ') }}</p>
                            <p class="text-caption text-ink-secondary">
                                Совпало: {{ collect($duplicates[$duplicate->id] ?? [])->map(fn ($f) => $f === 'phone' ? 'телефон' : 'email')->implode(', ') }}
                            </p>
                        </div>
                        <x-ui.button variant="secondary" :href="route('clients.show', $duplicate)" class="shrink-0">Открыть</x-ui.button>
                    </div>
                @endforeach

                <x-ui.button wire:click="save(true)">Продолжить создание</x-ui.button>
            </div>
        </x-ui.alert>
    @endif

    <x-ui.input id="name" label="Имя *" type="text" wire:model="name" required />
    <x-ui.input id="phone" label="Телефон" type="tel" wire:model="phone" placeholder="+7 917 123-45-67" />
    <x-ui.input id="email" label="Email" type="email" wire:model="email" />
    <x-ui.input id="social" label="Соцсеть или мессенджер" type="text" wire:model="social" />

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.select id="legal_type" label="Тип клиента" wire:model="legal_type">
            <option value="">—</option>
            @foreach ($legalTypes as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.input id="role" label="Роль" type="text" wire:model="role" placeholder="Например: невеста, организатор" />
    </div>

    <x-ui.input id="contact_time" label="Удобное время для связи" type="text" wire:model="contact_time" />
    <x-ui.textarea id="notes" label="Примечания" rows="4" wire:model="notes" />

    <div class="flex items-center gap-3">
        <x-ui.button type="submit">Сохранить</x-ui.button>
        @if ($client)
            <x-ui.button variant="text" :href="route('clients.show', $client)">Отмена</x-ui.button>
        @endif
    </div>
</form>
