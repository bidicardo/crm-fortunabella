<div class="space-y-6">
    <h1 class="text-2xl font-bold">Пользователи</h1>

    <section class="space-y-3">
        <h2 class="text-lg font-semibold">Сотрудники</h2>

        @error('users') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="py-2 pr-4">Имя</th>
                        <th class="py-2 pr-4">Email</th>
                        <th class="py-2 pr-4">Роль</th>
                        <th class="py-2 pr-4">Статус</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2 pr-4">{{ $user->name }}</td>
                            <td class="py-2 pr-4">{{ $user->email }}</td>
                            <td class="py-2 pr-4">{{ $user->isCreator() ? 'Создатель' : 'Участник' }}</td>
                            <td class="py-2 pr-4">{{ $user->isBlocked() ? 'Заблокирован' : 'Активен' }}</td>
                            <td class="py-2 text-right">
                                @unless ($user->isCreator())
                                    @if ($user->isBlocked())
                                        <button wire:click="unblock({{ $user->id }})"
                                            wire:confirm="Разблокировать {{ $user->name }}?"
                                            class="rounded-md border border-slate-300 px-3 py-1 dark:border-slate-600">Разблокировать</button>
                                    @else
                                        <button wire:click="block({{ $user->id }})"
                                            wire:confirm="Заблокировать {{ $user->name }}? Все его сессии будут завершены."
                                            class="rounded-md border border-red-300 px-3 py-1 text-red-600 dark:text-red-400">Заблокировать</button>
                                    @endif
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="text-lg font-semibold">Приглашения</h2>

        <button wire:click="createInvite" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">
            Создать приглашение
        </button>

        @if ($inviteUrl)
            <div x-data="{ copied: false }" class="space-y-2 rounded-md border border-amber-400 bg-amber-50 p-3 dark:bg-slate-800">
                <p class="text-sm">Ссылка действует 24 часа и показывается только сейчас. Скопируйте её и передайте сотруднику.</p>
                <div class="flex gap-2">
                    <input x-ref="url" type="text" readonly value="{{ $inviteUrl }}"
                        class="w-full rounded-md border border-slate-300 bg-transparent px-3 py-2 text-sm dark:border-slate-600"
                        x-on:focus="$el.select()">
                    <button type="button" class="rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600"
                        x-on:click="navigator.clipboard.writeText($refs.url.value); copied = true">
                        <span x-text="copied ? 'Скопировано' : 'Копировать'"></span>
                    </button>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700">
                        <th class="py-2 pr-4">Создано</th>
                        <th class="py-2 pr-4">Кто создал</th>
                        <th class="py-2">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invites as $invite)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2 pr-4">{{ $invite->created_at->format('d.m.Y H:i') }}</td>
                            <td class="py-2 pr-4">{{ $invite->creator->name }}</td>
                            <td class="py-2">
                                @if ($invite->used_at)
                                    использовано
                                @elseif ($invite->expires_at->isPast())
                                    истекло
                                @else
                                    активно
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-slate-500">Приглашений пока нет.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
