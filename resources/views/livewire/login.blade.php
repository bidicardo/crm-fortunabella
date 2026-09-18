<div class="mx-auto max-w-sm space-y-4">
    <h1 class="text-2xl font-bold">Вход</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" required autofocus
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
            @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm">Пароль</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 dark:border-slate-600">
            @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember"> Запомнить меня
        </label>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">
            Войти
        </button>
    </form>
</div>
