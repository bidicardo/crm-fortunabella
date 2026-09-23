{{-- Левое меню: только от 768 px. Свёрнутое состояние — класс sidebar-collapsed на <html>.
     Цвета sidebar-*: тёмно-коричневое в светлой теме, тёмное в тёмной; рамка фокуса внутри — своим цветом.
     Закреплено на высоту окна (sticky): иначе на длинной странице «Свернуть» уходит за нижний край.
     Пункты и «Свернуть» — кнопки на подложке; блоки разделены светлыми линиями. --}}
@php
    $button = 'focus-ring flex min-h-11 w-full items-center gap-3 rounded-md px-3 collapsed:justify-center collapsed:px-0';
    $idle = 'bg-sidebar-item hover:bg-sidebar-hover hover:text-white';
@endphp

<aside
    x-data
    class="hidden shrink-0 flex-col bg-sidebar text-body text-sidebar-ink [--color-accent:var(--color-sidebar-active)] md:sticky md:top-0 md:flex md:h-dvh md:w-64 md:self-start md:collapsed:w-16 dark:border-r dark:border-line"
>
    <div class="flex h-14 shrink-0 items-center border-b border-sidebar-line px-5 text-h4 font-semibold text-white collapsed:justify-center collapsed:px-0">
        <span class="collapsed:hidden">{{ config('app.name') }}</span>
        <span class="hidden collapsed:inline" aria-hidden="true">F</span>
    </div>

    <nav aria-label="Основное меню" class="flex-1 divide-y divide-sidebar-line overflow-y-auto px-2">
        @foreach (\App\Support\Navigation::groups() as $group)
            <div class="space-y-1.5 py-3">
                @if ($group['label'])
                    <p class="px-3 text-caption uppercase tracking-wide text-sidebar-label collapsed:hidden">{{ $group['label'] }}</p>
                @endif

                @foreach ($group['items'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        title="{{ $item['label'] }}"
                        @if ($item['current']) aria-current="page" @endif
                        class="{{ $button }} {{ $item['current'] ? 'bg-sidebar-current font-semibold text-white' : $idle }}"
                    >
                        <x-icon :name="$item['icon']" />
                        <span class="truncate collapsed:hidden">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-sidebar-line p-2">
        <button
            type="button"
            title="Свернуть меню"
            aria-label="Свернуть или развернуть меню"
            class="{{ $button }} {{ $idle }}"
            x-on:click="
                const c = document.documentElement.classList.toggle('sidebar-collapsed');
                try { localStorage.sidebar = c ? 'collapsed' : 'expanded'; } catch (e) {}
            "
        >
            <x-icon name="chevron-left" class="collapsed:rotate-180" />
            <span class="collapsed:hidden">Свернуть</span>
        </button>
    </div>
</aside>
