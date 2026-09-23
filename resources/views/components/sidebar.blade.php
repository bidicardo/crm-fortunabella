{{-- Левое меню: только от 768 px. Свёрнутое состояние — класс sidebar-collapsed на <html>.
     Цвета sidebar-*: коричневое в светлой теме, тёмное в тёмной; рамка фокуса внутри — своим цветом.
     Закреплено на высоту окна (sticky): иначе на длинной странице «Свернуть» уходит за нижний край. --}}
<aside
    x-data
    class="hidden shrink-0 flex-col bg-sidebar text-body text-sidebar-ink [--color-accent:var(--color-sidebar-active)] md:sticky md:top-0 md:flex md:h-dvh md:w-64 md:self-start md:collapsed:w-16 dark:border-r dark:border-line"
>
    <div class="flex h-14 items-center px-5 text-h4 font-semibold text-white collapsed:justify-center collapsed:px-0">
        <span class="collapsed:hidden">{{ config('app.name') }}</span>
        <span class="hidden collapsed:inline" aria-hidden="true">F</span>
    </div>

    <nav aria-label="Основное меню" class="flex-1 space-y-4 overflow-y-auto px-2 py-2">
        @foreach (\App\Support\Navigation::groups() as $group)
            <div class="space-y-1">
                @if ($group['label'])
                    <p class="px-3 pt-1 text-caption uppercase tracking-wide text-sidebar-label collapsed:hidden">{{ $group['label'] }}</p>
                @endif

                @foreach ($group['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        title="{{ $item['label'] }}"
                        @if ($item['current']) aria-current="page" @endif
                        class="focus-ring flex min-h-11 items-center gap-3 rounded-md px-3 hover:bg-sidebar-hover hover:text-white collapsed:justify-center {{ $item['current'] ? 'bg-sidebar-hover font-semibold text-white' : '' }}"
                    >
                        <x-icon :name="$item['icon']" class="{{ $item['current'] ? 'text-sidebar-active' : '' }}" />
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
        class="focus-ring flex h-12 items-center gap-3 border-t border-sidebar-hover px-5 hover:text-white collapsed:justify-center collapsed:px-0"
        x-on:click="
            const c = document.documentElement.classList.toggle('sidebar-collapsed');
            try { localStorage.sidebar = c ? 'collapsed' : 'expanded'; } catch (e) {}
        "
    >
        <x-icon name="chevron-left" class="collapsed:rotate-180" />
        <span class="collapsed:hidden">Свернуть</span>
    </button>
</aside>
