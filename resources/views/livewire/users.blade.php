@php
    $th = 'px-4 py-2 text-caption font-semibold';
    $td = 'px-4 py-2';
@endphp

<div class="max-w-3xl space-y-6">
    <section class="space-y-3">
        <h2 class="text-h3 font-semibold">Сотрудники</h2>

        @error('users') <x-ui.alert kind="error">{{ $message }}</x-ui.alert> @enderror

        <x-ui.table>
            <thead class="bg-surface">
                <tr>
                    <th class="{{ $th }}">Имя</th>
                    <th class="{{ $th }}">Email</th>
                    <th class="{{ $th }}">Роль</th>
                    <th class="{{ $th }}">Статус</th>
                    <th class="{{ $th }}"><span class="sr-only">Действия</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="border-t border-line">
                        <td class="{{ $td }}">{{ $user->name }}</td>
                        <td class="{{ $td }}">{{ $user->email }}</td>
                        <td class="{{ $td }}">{{ $user->isCreator() ? 'Создатель' : 'Участник' }}</td>
                        <td class="{{ $td }}">
                            <x-ui.badge :tone="$user->isBlocked() ? 'error' : 'success'">{{ $user->isBlocked() ? 'Заблокирован' : 'Активен' }}</x-ui.badge>
                        </td>
                        <td class="{{ $td }} text-right">
                            @unless ($user->isCreator())
                                @if ($user->isBlocked())
                                    <x-ui.button variant="secondary" wire:click="unblock({{ $user->id }})"
                                        wire:confirm="Разблокировать {{ $user->name }}?">Разблокировать</x-ui.button>
                                @else
                                    <x-ui.button variant="danger" wire:click="block({{ $user->id }})"
                                        wire:confirm="Заблокировать {{ $user->name }}? Все его сессии будут завершены.">Заблокировать</x-ui.button>
                                @endif
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </section>

    <section class="space-y-3">
        <h2 class="text-h3 font-semibold">Приглашения</h2>

        <x-ui.button wire:click="createInvite">Создать приглашение</x-ui.button>

        @if ($inviteUrl)
            <x-ui.alert kind="warning">
                {{-- navigator.clipboard есть только на HTTPS (и localhost); иначе копируем через выделение.
                     iOS выделяет текст только в поле без readonly и только через setSelectionRange.
                     Код — в методе, а не в x-on:click: выражение Alpine, начинающееся с комментария //,
                     не распознаётся как оператор и падает с синтаксической ошибкой. --}}
                <div
                    x-data="{
                        copied: false,
                        copy() {
                            const el = this.$refs.url;
                            const bySelection = () => {
                                el.readOnly = false;
                                el.focus();
                                el.setSelectionRange(0, el.value.length);
                                this.copied = document.execCommand('copy');
                                el.readOnly = true;
                                el.blur();
                            };
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(el.value).then(() => this.copied = true, bySelection);
                            } else {
                                bySelection();
                            }
                        },
                    }"
                    class="space-y-2"
                >
                    <p>Ссылка действует 24 часа и показывается только сейчас. Скопируйте её и передайте сотруднику.</p>
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <x-ui.input x-ref="url" type="text" readonly value="{{ $inviteUrl }}" aria-label="Ссылка-приглашение"
                                x-on:focus="$el.select()" />
                        </div>
                        <x-ui.button icon="copy" class="shrink-0" x-on:click="copy()">
                            <span x-text="copied ? 'Скопировано' : 'Копировать'"></span>
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.alert>
        @endif

        <x-ui.table>
            <thead class="bg-surface">
                <tr>
                    <th class="{{ $th }}">Создано</th>
                    <th class="{{ $th }}">Кто создал</th>
                    <th class="{{ $th }}">Статус</th>
                    <th class="{{ $th }}"><span class="sr-only">Действия</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invites as $invite)
                    <tr wire:key="invite-{{ $invite->id }}" class="border-t border-line">
                        <td class="{{ $td }}">{{ $invite->created_at->format('d.m.Y H:i') }}</td>
                        <td class="{{ $td }}">{{ $invite->creator->name }}</td>
                        <td class="{{ $td }}">
                            @if ($invite->used_at)
                                <x-ui.badge>использовано</x-ui.badge>
                            @elseif ($invite->revoked_at)
                                <x-ui.badge tone="error">деактивировано</x-ui.badge>
                            @elseif ($invite->expires_at->isPast())
                                <x-ui.badge tone="warning">истекло</x-ui.badge>
                            @else
                                <x-ui.badge tone="success">активно</x-ui.badge>
                            @endif
                        </td>
                        <td class="{{ $td }} text-right">
                            @if ($invite->isActive())
                                <x-ui.button variant="secondary" wire:click="revokeInvite({{ $invite->id }})"
                                    wire:confirm="Деактивировать приглашение? Ссылка перестанет работать.">Деактивировать</x-ui.button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="border-t border-line">
                        <td colspan="4" class="{{ $td }} text-ink-secondary">Приглашений пока нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.table>
    </section>
</div>
