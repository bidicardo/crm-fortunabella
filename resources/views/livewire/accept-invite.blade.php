<div class="mx-auto max-w-sm space-y-4">
    <h1 class="text-h3 font-bold">Регистрация по приглашению</h1>

    <form wire:submit="register" class="space-y-4">
        <x-ui.input id="name" label="Имя" type="text" wire:model="name" autocomplete="name" required autofocus />
        <x-ui.input id="email" label="Email" type="email" wire:model="email" autocomplete="username" required />

        {{-- Без name: при отправке без JavaScript пароль не должен попасть в адрес страницы --}}
        <x-ui.input id="password" label="Пароль (минимум 8 символов)" type="password" wire:model="password" autocomplete="new-password" required />
        <x-ui.input id="password_confirmation" label="Повторите пароль" type="password" wire:model="password_confirmation" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Зарегистрироваться</x-ui.button>
    </form>
</div>
