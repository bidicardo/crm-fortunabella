<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex min-h-screen">
            <x-sidebar />

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex h-14 items-center justify-between border-b border-slate-200 px-4 md:px-6 dark:border-slate-700">
                    <span class="truncate text-lg font-semibold">{{ $title ?? '' }}</span>
                    <x-user-menu />
                </header>

                <main class="min-w-0 flex-1 p-4 md:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
