<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        {{-- Ставим тему до отрисовки, чтобы не было мигания --}}
        <script>
            try {
                if (localStorage.theme === 'dark' || (!('theme' in localStorage) && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <header
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700"
        >
            <span class="font-semibold">{{ config('app.name') }}</span>
            <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('users') }}" class="text-sm underline">Пользователи</a>
                <span class="text-sm">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm dark:border-slate-600">Выйти</button>
                </form>
            @endauth
            <button
                type="button"
                class="rounded-md border border-slate-300 px-3 py-1.5 text-sm dark:border-slate-600"
                x-on:click="
                    dark = !dark;
                    document.documentElement.classList.toggle('dark', dark);
                    localStorage.theme = dark ? 'dark' : 'light';
                "
                x-text="dark ? 'Светлая тема' : 'Тёмная тема'"
            ></button>
            </div>
        </header>

        <main class="mx-auto max-w-3xl p-4">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
