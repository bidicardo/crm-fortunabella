<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Вход')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Слишком много попыток входа. Повторите через '.RateLimiter::availableIn($key).' сек.',
            ]);
        }

        // blocked_at => null: заблокированный получает ту же общую ошибку, что и при неверном пароле.
        $credentials = ['email' => $this->email, 'password' => $this->password, 'blocked_at' => null];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return $this->redirectIntended('/');
    }

    public function render()
    {
        return view('livewire.login');
    }
}
