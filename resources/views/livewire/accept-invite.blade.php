<div class="space-y-4">
    <h1 class="text-center text-h3 font-bold">Регистрация по приглашению</h1>

    <form wire:submit="register" class="space-y-4">
        <x-ui.input id="name" label="Имя" type="text" wire:model="name" autocomplete="name" required autofocus />
        <x-ui.input id="email" label="Email" type="email" wire:model="email" autocomplete="username" required />

        {{-- Без name: при отправке без JavaScript пароль не должен попасть в адрес страницы --}}
        <x-ui.input id="password" label="Пароль (минимум 8 символов)" type="password" wire:model="password" autocomplete="new-password" required />
        <x-ui.input id="password_confirmation" label="Повторите пароль" type="password" wire:model="password_confirmation" autocomplete="new-password" required />

        {{-- Во время отправки кнопка неактивна и крутит значок --}}
        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="register">
            <x-icon name="loading" class="h-4 w-4 animate-spin" wire:loading wire:target="register" />
            Зарегистрироваться
        </x-ui.button>
    </form>
</div>
