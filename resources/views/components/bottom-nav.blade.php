@php
    // Те же пункты, что и в левом меню: tab=true — вкладки, остальные уходят в «Ещё».
    $items = collect(\App\Support\Navigation::groups())->pluck('items')->flatten(1);
    $tabs = $items->where('tab', true);
    $more = $items->where('tab', false);
    $tab = 'focus-ring flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 px-1 text-caption';
@endphp

{{-- Нижняя панель: только уже 768 px. Переключение с левым меню — чистый CSS. --}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="md:hidden">
    <nav
        aria-label="Нижнее меню"
        class="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-bar pb-[env(safe-area-inset-bottom)]"
    >
        @foreach ($tabs as $item)
            <a
                href="{{ $item['url'] }}"
                @if ($item['current']) aria-current="page" @endif
                class="{{ $tab }} {{ $item['current'] ? 'font-semibold text-accent' : 'text-ink-secondary' }}"
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
                class="{{ $tab }} {{ $more->contains('current', true) ? 'font-semibold text-accent' : 'text-ink-secondary' }}"
            >
                <x-icon name="more" class="h-6 w-6" />
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
                class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-surface pb-[env(safe-area-inset-bottom)] shadow-panel"
            >
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-h4 font-semibold">Ещё</span>
                    <x-ui.icon-button icon="close" label="Закрыть" x-on:click="open = false" />
                </div>

                @foreach ($more as $item)
                    <a
                        href="{{ $item['url'] }}"
                        @if ($item['current']) aria-current="page" @endif
                        class="focus-ring flex min-h-14 items-center gap-4 px-4 text-body-md hover:bg-line/50 {{ $item['current'] ? 'font-semibold text-accent' : '' }}"
                    >
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
