<div class="mx-auto max-w-sm space-y-4">
    <h1 class="text-h3 font-bold">Вход</h1>

    @if (session('blocked'))
        <x-ui.alert kind="error">{{ session('blocked') }}</x-ui.alert>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-ui.input id="email" label="Email" type="email" wire:model="email" autocomplete="username" required autofocus />

        {{-- Без name: при отправке без JavaScript пароль не должен попасть в адрес страницы --}}
        <x-ui.input id="password" label="Пароль" type="password" wire:model="password" autocomplete="current-password" required />

        <x-ui.checkbox id="remember" label="Запомнить меня" wire:model="remember" />

        <x-ui.button type="submit" class="w-full">Войти</x-ui.button>
    </form>
</div>
