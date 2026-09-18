@php
    $link = fn (string $route) => \Illuminate\Support\Facades\Route::has($route);
    $item = 'block px-4 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700';
@endphp

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative">
    <button
        type="button"
        class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
        aria-haspopup="true"
        x-bind:aria-expanded="open"
        x-on:click="open = !open"
    >
        <span class="max-w-40 truncate">{{ auth()->user()->name }}</span>
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
    </button>

    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-56 rounded-md border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
        @if ($link('profile'))
            <a href="{{ route('profile') }}" class="{{ $item }}">Профиль</a>
        @endif

        <x-theme-toggle class="{{ $item }} w-full text-left" />

        @if ($link('notifications.index'))
            <a href="{{ route('notifications.index') }}" class="{{ $item }}">Уведомления</a>
        @endif

        @if ($link('settings.telegram'))
            <a href="{{ route('settings.telegram') }}" class="{{ $item }}">Telegram</a>
        @endif

        @can('manage-users')
            <a href="{{ route('users') }}" class="{{ $item }}">Пользователи</a>
        @endcan

        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200 dark:border-slate-700">
            @csrf
            <button type="submit" class="{{ $item }} w-full text-left">Выйти</button>
        </form>
    </div>
</div>
