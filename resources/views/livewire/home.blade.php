<div class="space-y-4">
    <h1 class="text-2xl font-bold">Fortuna CRM</h1>
    <p class="text-slate-600 dark:text-slate-300">Заглушка главной страницы: проверка стека Tailwind + Alpine + Livewire.</p>

    <div class="flex items-center gap-3">
        <button wire:click="increment" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">
            Livewire: +1
        </button>
        <span>Счётчик (на сервере): <strong>{{ $count }}</strong></span>
    </div>

    <div x-data="{ open: false }">
        <button x-on:click="open = !open" class="rounded-md border border-slate-300 px-4 py-2 dark:border-slate-600">
            Alpine: <span x-text="open ? 'скрыть' : 'показать'"></span>
        </button>
        <p x-show="open" class="mt-2 rounded-md bg-slate-100 p-3 dark:bg-slate-800">Это работает без обращения к серверу.</p>
    </div>
</div>
