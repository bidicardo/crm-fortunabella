<?php

use App\Livewire\Login;
use App\Livewire\Users;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function addSession(User $user, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp,
    ]);
}

it('blocks a member from the users page', function () {
    $creator = User::factory()->creator()->create();
    $member = User::factory()->create();

    Livewire::actingAs($creator)->test(Users::class)->call('block', $member->id);

    expect($member->fresh()->isBlocked())->toBeTrue();
});

it('ends the sessions and remember-me tokens of a blocked user', function () {
    $member = User::factory()->create(['remember_token' => 'old-token']);
    $other = User::factory()->create();
    addSession($member, 'member-session');
    addSession($other, 'other-session');

    $member->block();

    expect(DB::table('sessions')->pluck('id')->all())->toBe(['other-session'])
        ->and($member->fresh()->remember_token)->not->toBe('old-token');
});

it('kicks a blocked user out on the next request', function () {
    $member = User::factory()->create();
    $this->actingAs($member)->get('/')->assertOk();

    $member->block();

    $this->get('/')->assertRedirect('/login')->assertSessionHas('blocked');
    $this->assertGuest();
});

it('shows the blocked message on the login page', function () {
    $this->withSession(['blocked' => 'Учётная запись заблокирована.'])->get('/login')->assertSee('Учётная запись заблокирована.');
});

it('does not let a blocked user log in, but lets them in after unblocking', function () {
    $member = User::factory()->create(['password' => 'secret-pass-1']);
    $creator = User::factory()->creator()->create();

    $member->block();
    Livewire::test(Login::class)->set('email', $member->email)->set('password', 'secret-pass-1')->call('login')->assertHasErrors(['email']);
    $this->assertGuest();

    Livewire::actingAs($creator)->test(Users::class)->call('unblock', $member->id);
    expect($member->fresh()->isBlocked())->toBeFalse();

    auth()->logout();
    Livewire::test(Login::class)->set('email', $member->email)->set('password', 'secret-pass-1')->call('login')->assertRedirect('/');
    $this->assertAuthenticatedAs($member);
});

it('does not allow blocking the creator or yourself', function () {
    $creator = User::factory()->creator()->create();
    $member = User::factory()->create();

    Livewire::actingAs($member)->test(Users::class)->call('block', $creator->id)->assertHasErrors(['users']);
    Livewire::actingAs($member)->test(Users::class)->call('block', $member->id)->assertHasErrors(['users']);
    Livewire::actingAs($creator)->test(Users::class)->call('block', $creator->id)->assertHasErrors(['users']);

    expect($creator->fresh()->isBlocked())->toBeFalse()->and($member->fresh()->isBlocked())->toBeFalse();
});

it('lists users with their role and status', function () {
    $creator = User::factory()->creator()->create(['name' => 'Главный Создатель']);
    User::factory()->blocked()->create(['name' => 'Заблокированный Иван']);

    Livewire::actingAs($creator)->test(Users::class)
        ->assertSee('Главный Создатель')
        ->assertSee('Создатель')
        ->assertSee('Заблокированный Иван')
        ->assertSee('Заблокирован')
        ->assertSee('Разблокировать');
});
