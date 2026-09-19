<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex min-h-dvh">
            <x-sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-14 items-center justify-between border-b border-slate-200 px-4 md:px-6 dark:border-slate-700">
                    <div class="flex min-w-0 items-center gap-2">
                        {{-- Стрелка ведёт в родительский раздел (Navigation::backUrl), а не по истории браузера --}}
                        @if ($backUrl = \App\Support\Navigation::backUrl())
                            <a
                                href="{{ $backUrl }}"
                                aria-label="Назад"
                                title="Назад"
                                class="-ml-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-md hover:bg-slate-100 dark:hover:bg-slate-800"
                            >
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
                            </a>
                        @endif
                        <span class="truncate text-lg font-semibold">{{ $title ?? '' }}</span>
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
