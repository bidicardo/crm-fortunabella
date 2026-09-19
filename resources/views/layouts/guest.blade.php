<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <div class="mx-auto flex min-h-dvh w-full max-w-sm flex-col justify-center gap-6 p-4">
            <p class="text-center text-xl font-semibold">{{ config('app.name') }}</p>

            {{ $slot }}

            <x-theme-toggle class="mx-auto text-xs text-slate-500 underline dark:text-slate-400" />
        </div>

        @livewireScripts
    </body>
</html>
