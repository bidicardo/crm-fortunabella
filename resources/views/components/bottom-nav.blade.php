@php
    // Те же пункты, что и в левом меню: tab=true — вкладки, остальные уходят в «Ещё».
    $items = collect(\App\Support\Navigation::groups())->pluck('items')->flatten(1);
    $tabs = $items->where('tab', true);
    $more = $items->where('tab', false);
    $tab = 'flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 px-1 text-xs';
@endphp

{{-- Нижняя панель: только уже 768 px. Переключение с левым меню — чистый CSS. --}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="md:hidden">
    <nav
        aria-label="Нижнее меню"
        class="fixed inset-x-0 bottom-0 z-30 flex border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] dark:border-slate-700 dark:bg-slate-900"
    >
        @foreach ($tabs as $item)
            <a
                href="{{ $item['url'] }}"
                @if ($item['current']) aria-current="page" @endif
                class="{{ $tab }} {{ $item['current'] ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' }}"
            >
                <x-icon :name="$item['icon']" class="h-6 w-6" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        @if ($more->isNotEmpty())
            <button
                type="button"
                aria-haspopup="dialog"
                x-bind:aria-expanded="open"
                x-on:click="open = true"
                class="{{ $tab }} {{ $more->contains('current', true) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400' }}"
            >
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h.01M12 12h.01M19 12h.01" stroke-width="3" /></svg>
                <span>Ещё</span>
            </button>
        @endif
    </nav>

    @if ($more->isNotEmpty())
        <div x-show="open" x-cloak class="fixed inset-0 z-40">
            <div class="absolute inset-0 bg-black/50" x-on:click="open = false"></div>

            <div
                role="dialog"
                aria-label="Ещё"
                class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] dark:bg-slate-800"
            >
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="font-semibold">Ещё</span>
                    <button type="button" aria-label="Закрыть" class="flex h-11 w-11 items-center justify-center rounded-md hover:bg-slate-100 dark:hover:bg-slate-700" x-on:click="open = false">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </div>

                @foreach ($more as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @if ($item['current']) aria-current="page" @endif
                        class="flex min-h-14 items-center gap-4 px-4 text-base hover:bg-slate-100 dark:hover:bg-slate-700 {{ $item['current'] ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '' }}"
                    >
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
