<?php

use App\Livewire\AcceptInvite;
use App\Livewire\Login;
use App\Livewire\Users;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function loginAs(string $email, string $password)
{
    return Livewire::test(Login::class)->set('email', $email)->set('password', $password)->call('login');
}

it('runs the whole access flow end to end', function () {
    // Команда создаёт первого создателя.
    $this->artisan('crm:create-creator', ['--name' => 'Создатель', '--email' => 'creator@example.test', '--password' => 'creator-pass-1'])
        ->assertSuccessful();

    // Создатель входит и создаёт приглашение.
    loginAs('creator@example.test', 'creator-pass-1')->assertRedirect('/');
    $token = basename(Livewire::test(Users::class)->call('createInvite')->get('inviteUrl'));
    auth()->logout();

    // Сотрудник регистрируется по ссылке и попадает в систему.
    Livewire::test(AcceptInvite::class, ['token' => $token])
        ->set('name', 'Сотрудник')
        ->set('email', 'member@example.test')
        ->set('password', 'member-pass-1')
        ->set('password_confirmation', 'member-pass-1')
        ->call('register')
        ->assertRedirect('/');

    $member = User::firstWhere('email', 'member@example.test');
    expect(auth()->id())->toBe($member->id)->and($member->isCreator())->toBeFalse();
    auth()->logout();

    // Повторное открытие ссылки даёт 404.
    $this->get("/invite/{$token}")->assertNotFound();

    // Сотрудник входит по своему паролю, но управлять пользователями не может.
    loginAs('member@example.test', 'member-pass-1')->assertRedirect('/');
    $this->get('/users')->assertForbidden();
    auth()->logout();

    // Создатель блокирует сотрудника; его сессии удаляются.
    loginAs('creator@example.test', 'creator-pass-1')->assertRedirect('/');
    DB::table('sessions')->insert(['id' => 'member-session', 'user_id' => $member->id, 'payload' => '', 'last_activity' => now()->timestamp]);
    Livewire::test(Users::class)->call('block', $member->id);
    expect(DB::table('sessions')->where('user_id', $member->id)->count())->toBe(0);
    auth()->logout();

    // Заблокированный сотрудник войти не может.
    loginAs('member@example.test', 'member-pass-1')->assertHasErrors(['email']);
    $this->assertGuest();
});
