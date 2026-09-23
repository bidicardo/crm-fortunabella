<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-dvh bg-page text-body text-ink">
        <div class="mx-auto flex min-h-dvh w-full max-w-sm flex-col justify-center gap-6 p-4">
            <p class="text-center text-h3 font-semibold">{{ config('app.name') }}</p>

            {{ $slot }}

            <x-theme-toggle class="focus-ring mx-auto min-h-11 rounded-md px-2 text-caption text-ink-secondary underline underline-offset-2" />
        </div>

        @livewireScripts
    </body>
</html>
