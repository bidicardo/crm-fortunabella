<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class AcceptInvite extends Component
{
    public string $token = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        // Одна и та же страница для несуществующей, использованной и истёкшей ссылки — причину не раскрываем.
        if (! Invite::findValid($token)) {
            abort(response()->view('invite-invalid', [], 404));
        }

        $this->token = $token;
    }

    public function register()
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $invite = Invite::findValid($this->token);

        if (! $invite) {
            return $this->redirect(route('invite.accept', $this->token));
        }

        try {
            $user = DB::transaction(function () use ($data, $invite) {
                $user = new User($data);
                $user->role = Role::Member;
                $user->save();

                // Если ссылку успели использовать параллельно — откатываем и создание пользователя.
                if (! $invite->markUsed($user)) {
                    throw new ModelNotFoundException;
                }

                return $user;
            });
        } catch (ModelNotFoundException) {
            return $this->redirect(route('invite.accept', $this->token));
        }

        Auth::login($user);
        session()->regenerate();

        return $this->redirect('/');
    }

    public function render()
    {
        return view('livewire.accept-invite');
    }
}
