<div class="space-y-4">
    <h1 class="text-center text-h3 font-bold">Вход</h1>

    @if (session('blocked'))
        <x-ui.alert kind="error">{{ session('blocked') }}</x-ui.alert>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-ui.input id="email" label="Email" type="email" wire:model="email" autocomplete="username" required autofocus />

        {{-- Без name: при отправке без JavaScript пароль не должен попасть в адрес страницы --}}
        <x-ui.input id="password" label="Пароль" type="password" wire:model="password" autocomplete="current-password" required />

        <x-ui.checkbox id="remember" label="Запомнить меня" wire:model="remember" />

        {{-- Во время отправки кнопка неактивна и крутит значок --}}
        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <x-icon name="login" class="h-4 w-4" wire:loading.remove wire:target="login" />
            <x-icon name="loading" class="h-4 w-4 animate-spin" wire:loading wire:target="login" />
            Войти
        </x-ui.button>
    </form>
</div>
