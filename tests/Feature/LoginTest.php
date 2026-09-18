<?php

use App\Livewire\Login;
use App\Models\User;
use Livewire\Livewire;

function attemptLogin(string $email, string $password)
{
    return Livewire::test(Login::class)->set('email', $email)->set('password', $password)->call('login');
}

it('logs a user in with correct credentials', function () {
    $user = User::factory()->create(['password' => 'secret-pass-1']);

    attemptLogin($user->email, 'secret-pass-1')->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password with a generic message', function () {
    $user = User::factory()->create(['password' => 'secret-pass-1']);

    attemptLogin($user->email, 'wrong')->assertHasErrors(['email'])->assertSee('Неверный email или пароль.');

    $this->assertGuest();
});

it('gives a blocked user the same generic error as a wrong password', function () {
    $user = User::factory()->blocked()->create(['password' => 'secret-pass-1']);

    attemptLogin($user->email, 'secret-pass-1')->assertHasErrors(['email'])->assertSee('Неверный email или пароль.');

    $this->assertGuest();
});

it('throttles login attempts after five failures', function () {
    $user = User::factory()->create(['password' => 'secret-pass-1']);

    foreach (range(1, 5) as $i) {
        attemptLogin($user->email, 'wrong');
    }

    attemptLogin($user->email, 'secret-pass-1')->assertHasErrors(['email'])->assertSee('Слишком много попыток');

    $this->assertGuest();
});

it('redirects guests from the home page to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

it('redirects authenticated users away from the login page', function () {
    $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
});

it('shows the login page to guests', function () {
    $this->get('/login')->assertOk()->assertSeeLivewire(Login::class);
});

it('logs a user out', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');

    $this->assertGuest();
});

it('shows the user name and a logout button in the header', function () {
    $user = User::factory()->create(['name' => 'Иван Тестов']);

    $this->actingAs($user)->get('/')->assertSee('Иван Тестов')->assertSee('Выйти');
});

it('has no registration or password reset routes', function () {
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
});
