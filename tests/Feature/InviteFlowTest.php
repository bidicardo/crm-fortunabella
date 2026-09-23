<?php

use App\Enums\Role;
use App\Livewire\AcceptInvite;
use App\Livewire\Users;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

function registerVia(string $token, array $overrides = [])
{
    return Livewire::test(AcceptInvite::class, ['token' => $token])
        ->set('name', $overrides['name'] ?? 'Новый Сотрудник')
        ->set('email', $overrides['email'] ?? 'new@example.test')
        ->set('password', $overrides['password'] ?? 'secret-pass-1')
        ->set('password_confirmation', $overrides['password_confirmation'] ?? 'secret-pass-1')
        ->call('register');
}

it('lets the creator create an invite and shows the link once', function () {
    $creator = User::factory()->creator()->create();

    $component = Livewire::actingAs($creator)->test(Users::class)->call('createInvite');

    $url = $component->get('inviteUrl');
    $token = basename($url);

    expect(Invite::count())->toBe(1)
        ->and(Invite::findValid($token))->not->toBeNull();

    $component->assertSee($url);

    // После перезагрузки страницы ссылку уже не восстановить.
    Livewire::actingAs($creator)->test(Users::class)->assertDontSee($token)->assertSee('активно');
});

it('lists invites with their status', function () {
    Invite::factory()->used()->create();
    Invite::factory()->expired()->create();

    Livewire::actingAs(User::factory()->create())->test(Users::class)
        ->assertSee('использовано')
        ->assertSee('истекло');
});

it('registers a member by invite link and logs them in', function () {
    [$invite, $token] = Invite::issue(User::factory()->creator()->create());

    registerVia($token)->assertHasNoErrors()->assertRedirect('/');

    $user = User::firstWhere('email', 'new@example.test');

    expect($user->role)->toBe(Role::Member)
        ->and($user->name)->toBe('Новый Сотрудник')
        ->and(Auth::id())->toBe($user->id)
        ->and($invite->fresh()->used_at)->not->toBeNull()
        ->and($invite->fresh()->user_id)->toBe($user->id);
});

it('shows the registration form for a valid link', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    $this->get("/invite/{$token}")->assertOk()->assertSeeLivewire(AcceptInvite::class);
});

it('closes the link after it was used', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());
    registerVia($token);
    Auth::logout();

    $this->get("/invite/{$token}")->assertNotFound()->assertSee('Ссылка недействительна');
});

it('shows the same 404 page for unknown, expired and used links', function () {
    Invite::factory()->expired()->create(['token_hash' => hash('sha256', 'expired-token')]);
    Invite::factory()->used()->create(['token_hash' => hash('sha256', 'used-token')]);

    foreach (['unknown-token', 'expired-token', 'used-token'] as $token) {
        $this->get("/invite/{$token}")->assertNotFound()->assertSee('Ссылка недействительна')->assertDontSee('Регистрация по приглашению');
    }
});

it('does not burn the invite on a duplicate email', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());
    User::factory()->create(['email' => 'taken@example.test']);
    $usersBefore = User::count();

    registerVia($token, ['email' => 'taken@example.test'])->assertHasErrors(['email']);

    expect(User::count())->toBe($usersBefore)
        ->and(Invite::findValid($token))->not->toBeNull();
});

it('validates the password', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    registerVia($token, ['password' => 'short', 'password_confirmation' => 'short'])->assertHasErrors(['password']);
    registerVia($token, ['password_confirmation' => 'different-pass'])->assertHasErrors(['password']);

    expect(Invite::findValid($token))->not->toBeNull()->and(User::count())->toBe(1);
});

it('does not create a user when the invite was used while the form was open', function () {
    [$invite, $token] = Invite::issue(User::factory()->creator()->create());
    $usersBefore = User::count();

    $component = Livewire::test(AcceptInvite::class, ['token' => $token])
        ->set('name', 'Опоздавший')
        ->set('email', 'late@example.test')
        ->set('password', 'secret-pass-1')
        ->set('password_confirmation', 'secret-pass-1');

    $invite->markUsed(User::factory()->create());

    $component->call('register')->assertRedirect(route('invite.accept', $token));

    expect(User::where('email', 'late@example.test')->exists())->toBeFalse()
        ->and(User::count())->toBe($usersBefore + 1);
});

it('sends authenticated users away from the invite page', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    $this->actingAs(User::factory()->create())->get("/invite/{$token}")->assertRedirect('/');
});

it('rate limits the invite page', function () {
    foreach (range(1, 10) as $i) {
        $this->get('/invite/guess-'.$i)->assertNotFound();
    }

    $this->get('/invite/guess-11')->assertStatus(429);
});

it('lets the creator deactivate an invite so its link stops working', function () {
    $creator = User::factory()->creator()->create();

    $component = Livewire::actingAs($creator)->test(Users::class)->call('createInvite');
    $token = basename($component->get('inviteUrl'));
    $invite = Invite::sole();

    $component->assertSee('Деактивировать')
        ->call('revokeInvite', $invite->id)
        ->assertSet('inviteUrl', null)
        ->assertSee('деактивировано')
        ->assertDontSee('Деактивировать');

    expect($invite->fresh()->revoked_at)->not->toBeNull()
        ->and(Invite::findValid($token))->toBeNull();

    // Страница приглашения — только для гостей, поэтому ссылку открывает уже не создатель.
    Auth::logout();
    $this->get("/invite/{$token}")->assertNotFound();
});

it('does not deactivate used or expired invites', function () {
    $used = Invite::factory()->used()->create();
    $expired = Invite::factory()->expired()->create();

    expect($used->revoke())->toBeFalse()
        ->and($expired->revoke())->toBeFalse()
        ->and($used->fresh()->revoked_at)->toBeNull()
        ->and($expired->fresh()->revoked_at)->toBeNull();
});

it('does not let a deactivated invite be used for registration', function () {
    [$invite] = Invite::issue(User::factory()->creator()->create());
    $invite->revoke();

    expect($invite->markUsed(User::factory()->create()))->toBeFalse();
});
