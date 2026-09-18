<?php

use App\Enums\Role;
use App\Models\User;

it('creates members by default', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(Role::Member)
        ->and($user->isCreator())->toBeFalse()
        ->and($user->isBlocked())->toBeFalse();
});

it('recognises creators and blocked users', function () {
    expect(User::factory()->creator()->create()->isCreator())->toBeTrue()
        ->and(User::factory()->blocked()->create()->isBlocked())->toBeTrue();
});

it('does not allow mass assignment of role and blocked_at', function () {
    $user = new User(['name' => 'A', 'email' => 'a@example.test', 'password' => 'secret123', 'role' => 'creator', 'blocked_at' => now()]);

    expect($user->role)->toBeNull()->and($user->blocked_at)->toBeNull();
});
