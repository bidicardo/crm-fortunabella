<?php

namespace App\Livewire;

use App\Models\Invite;
use Livewire\Component;

class Users extends Component
{
    // Полная ссылка живёт только в состоянии страницы: после перезагрузки восстановить её нельзя.
    public ?string $inviteUrl = null;

    public function createInvite(): void
    {
        [, $token] = Invite::issue(auth()->user());

        $this->inviteUrl = route('invite.accept', $token);
    }

    public function render()
    {
        return view('livewire.users', [
            'invites' => Invite::with('creator')->latest()->get(),
        ]);
    }
}
