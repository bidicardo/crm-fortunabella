<?php

namespace App\Livewire;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Users extends Component
{
    // Полная ссылка живёт только в состоянии страницы: после перезагрузки восстановить её нельзя.
    public ?string $inviteUrl = null;

    public function createInvite(): void
    {
        Gate::authorize('manage-users');

        [, $token] = Invite::issue(auth()->user());

        $this->inviteUrl = route('invite.accept', $token);
    }

    public function block(int $id): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($id);

        if ($user->isCreator() || $user->is(auth()->user())) {
            $this->addError('users', 'Создателя и самого себя заблокировать нельзя.');

            return;
        }

        $user->block();
    }

    public function unblock(int $id): void
    {
        Gate::authorize('manage-users');

        User::findOrFail($id)->unblock();
    }

    public function render()
    {
        return view('livewire.users', [
            'invites' => Invite::with('creator')->latest()->get(),
            'users' => User::orderBy('id')->get(),
        ]);
    }
}
