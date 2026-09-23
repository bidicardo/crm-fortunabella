<div class="max-w-3xl space-y-4">
    <p class="text-ink-secondary">Заглушка главной страницы: проверка стека Tailwind + Alpine + Livewire.</p>

    <div class="flex flex-wrap items-center gap-3">
        <x-ui.button wire:click="increment">Livewire: +1</x-ui.button>
        <span>Счётчик (на сервере): <strong>{{ $count }}</strong></span>
    </div>

    <div x-data="{ open: false }">
        <x-ui.button variant="secondary" x-on:click="open = !open">
            Alpine: <span x-text="open ? 'скрыть' : 'показать'"></span>
        </x-ui.button>
        <p x-show="open" x-cloak class="mt-2 rounded-md bg-surface p-3">Это работает без обращения к серверу.</p>
    </div>
</div>
