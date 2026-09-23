@php
    $link = fn (string $route) => \Illuminate\Support\Facades\Route::has($route);
    $item = 'focus-ring flex min-h-11 w-full items-center px-4 text-left text-body hover:bg-line/50';
@endphp

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative">
    <button
        type="button"
        class="focus-ring flex min-h-11 items-center gap-2 rounded-md px-3 text-body hover:bg-line/50"
        aria-haspopup="true"
        x-bind:aria-expanded="open"
        x-on:click="open = !open"
    >
        <span class="max-w-40 truncate">{{ auth()->user()->name }}</span>
        <x-icon name="chevron-down" class="h-4 w-4" />
    </button>

    <x-ui.panel x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-56">
        @if ($link('profile'))
            <a href="{{ route('profile') }}" class="{{ $item }}">Профиль</a>
        @endif

        <x-theme-toggle class="{{ $item }}" />

        @if ($link('notifications.index'))
            <a href="{{ route('notifications.index') }}" class="{{ $item }}">Уведомления</a>
        @endif

        @if ($link('settings.telegram'))
            <a href="{{ route('settings.telegram') }}" class="{{ $item }}">Telegram</a>
        @endif

        @can('manage-users')
            <a href="{{ route('users') }}" class="{{ $item }}">Пользователи</a>
        @endcan

        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-line pt-1">
            @csrf
            <button type="submit" class="{{ $item }}">Выйти</button>
        </form>
    </x-ui.panel>
</div>
