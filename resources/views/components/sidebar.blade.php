{{-- Левое меню: только от 768 px. Свёрнутое состояние — класс sidebar-collapsed на <html>. --}}
<aside
    x-data
    class="hidden shrink-0 flex-col bg-slate-900 text-slate-300 md:flex md:w-64 md:collapsed:w-16 dark:border-r dark:border-slate-800"
>
    <div class="flex h-14 items-center px-5 font-semibold text-white collapsed:justify-center collapsed:px-0">
        <span class="collapsed:hidden">{{ config('app.name') }}</span>
        <span class="hidden collapsed:inline" aria-hidden="true">F</span>
    </div>

    <nav aria-label="Основное меню" class="flex-1 space-y-4 overflow-y-auto px-2 py-2">
        @foreach (\App\Support\Navigation::groups() as $group)
            <div class="space-y-1">
                @if ($group['label'])
                    <p class="px-3 pt-1 text-xs uppercase tracking-wide text-slate-500 collapsed:hidden">{{ $group['label'] }}</p>
                @endif

                @foreach ($group['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        title="{{ $item['label'] }}"
                        @if ($item['current']) aria-current="page" @endif
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-slate-800 hover:text-white collapsed:justify-center {{ $item['current'] ? 'bg-slate-800 text-white' : '' }}"
                    >
                        <x-icon :name="$item['icon']" />
                        <span class="truncate collapsed:hidden">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <button
        type="button"
        title="Свернуть меню"
        aria-label="Свернуть или развернуть меню"
        class="flex h-12 items-center gap-3 border-t border-slate-800 px-5 text-sm hover:text-white collapsed:justify-center collapsed:px-0"
        x-on:click="
            const c = document.documentElement.classList.toggle('sidebar-collapsed');
            try { localStorage.sidebar = c ? 'collapsed' : 'expanded'; } catch (e) {}
        "
    >
        <svg class="h-5 w-5 shrink-0 collapsed:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
        <span class="collapsed:hidden">Свернуть</span>
    </button>
</aside>
