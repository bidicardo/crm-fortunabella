<?php

use App\Livewire\AcceptInvite;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('stores registered passwords only as hashes', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    Livewire::test(AcceptInvite::class, ['token' => $token])
        ->set('name', 'Сотрудник')
        ->set('email', 'member@example.test')
        ->set('password', 'member-pass-1')
        ->set('password_confirmation', 'member-pass-1')
        ->call('register');

    $stored = DB::table('users')->where('email', 'member@example.test')->value('password');

    expect($stored)->not->toBe('member-pass-1')
        ->and(password_get_info($stored)['algoName'])->not->toBe('unknown')
        ->and(Hash::check('member-pass-1', $stored))->toBeTrue();
});

it('never stores a plain invite token anywhere in the invites table', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    expect(json_encode(DB::table('invites')->get()))->not->toContain($token);
});

it('has no registration or password reset routes', function () {
    $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

    expect($uris->filter(fn ($uri) => preg_match('/register|password|forgot|reset/i', $uri)))->toBeEmpty();
});

it('has no named password fields that a submit without JavaScript could put into the URL', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    $this->get('/login')->assertSee('id="password"', false)->assertDontSee('name="password"', false);
    $this->get("/invite/{$token}")
        ->assertSee('id="password"', false)
        ->assertDontSee('name="password"', false)
        ->assertDontSee('name="password_confirmation"', false);
});
