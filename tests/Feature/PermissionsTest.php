<?php

use App\Livewire\Users;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

it('lets the creator open the users page', function () {
    $this->actingAs(User::factory()->creator()->create())->get('/users')->assertOk();
});

it('forbids a member from opening the users page', function () {
    $this->actingAs(User::factory()->create())->get('/users')->assertForbidden();
});

it('redirects guests from the users page to the login page', function () {
    $this->get('/users')->assertRedirect('/login');
});

it('allows manage-users only for the creator', function () {
    expect(Gate::forUser(User::factory()->creator()->create())->allows('manage-users'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('manage-users'))->toBeFalse();
});

it('forbids a member from creating invites', function () {
    Livewire::actingAs(User::factory()->create())->test(Users::class)->call('createInvite')->assertForbidden();

    expect(Invite::count())->toBe(0);
});

it('forbids a member from deactivating invites', function () {
    $invite = Invite::factory()->create();

    Livewire::actingAs(User::factory()->create())->test(Users::class)->call('revokeInvite', $invite->id)->assertForbidden();

    expect($invite->fresh()->revoked_at)->toBeNull();
});

it('forbids a member from blocking users', function () {
    $member = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($member)->test(Users::class)->call('block', $other->id)->assertForbidden();
    Livewire::actingAs($member)->test(Users::class)->call('block', $member->id)->assertForbidden();

    expect($other->fresh()->isBlocked())->toBeFalse()->and($member->fresh()->isBlocked())->toBeFalse();
});

it('forbids a member from unblocking users', function () {
    $blocked = User::factory()->blocked()->create();

    Livewire::actingAs(User::factory()->create())->test(Users::class)->call('unblock', $blocked->id)->assertForbidden();

    expect($blocked->fresh()->isBlocked())->toBeTrue();
});

it('shows the users menu item only to the creator', function () {
    $this->actingAs(User::factory()->creator()->create())->get('/')->assertSee('Пользователи');
    $this->actingAs(User::factory()->create())->get('/')->assertDontSee('Пользователи');
});
