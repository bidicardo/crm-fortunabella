<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-page text-body text-ink">
        <div class="flex min-h-dvh">
            <x-sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-14 items-center justify-between border-b border-line bg-bar px-4 md:px-6">
                    <div class="flex min-w-0 items-center gap-2">
                        {{-- Стрелка ведёт в родительский раздел (Navigation::backUrl), а не по истории браузера --}}
                        @if ($backUrl = \App\Support\Navigation::backUrl())
                            <a
                                href="{{ $backUrl }}"
                                aria-label="Назад"
                                title="Назад"
                                class="focus-ring -ml-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-md hover:bg-line/50"
                            >
                                <x-icon name="chevron-left" />
                            </a>
                        @endif
                        <span id="page-title" class="truncate text-h4 font-semibold">{{ $title ?? '' }}</span>
                    </div>
                    <x-user-menu />
                </header>

                {{-- Нижний отступ на телефоне: чтобы нижняя панель не перекрывала контент --}}
                <main class="min-w-0 flex-1 p-4 pb-[calc(5rem+env(safe-area-inset-bottom))] md:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-bottom-nav />

        @livewireScripts
    </body>
</html>
