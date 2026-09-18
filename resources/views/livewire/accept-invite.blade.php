<div class="mx-auto max-w-sm space-y-4">
    <h1 class="text-2xl font-bold">Регистрация по приглашению</h1>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="name" class="mb-1 block text-sm">Имя</label>
            <input id="name" type="text" wire:model="name" autocomplete="name" required autofocus
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" required
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm">Пароль (минимум 8 символов)</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password" required
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
            @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm">Повторите пароль</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
        </div>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">
            Зарегистрироваться
        </button>
    </form>
</div>
