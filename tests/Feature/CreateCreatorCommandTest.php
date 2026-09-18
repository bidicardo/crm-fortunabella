<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates the first creator with a hashed password', function () {
    $this->artisan('crm:create-creator', ['--name' => 'Test Creator', '--email' => 'creator@example.test', '--password' => 'secret-pass-1'])
        ->assertSuccessful();

    $user = User::firstWhere('email', 'creator@example.test');

    expect($user->role)->toBe(Role::Creator)
        ->and($user->password)->not->toBe('secret-pass-1')
        ->and(Hash::check('secret-pass-1', $user->password))->toBeTrue();
});

it('refuses to create a second creator', function () {
    User::factory()->creator()->create();

    $this->artisan('crm:create-creator', ['--name' => 'Other', '--email' => 'other@example.test', '--password' => 'secret-pass-1'])
        ->assertFailed();

    expect(User::where('role', Role::Creator)->count())->toBe(1);
});

it('rejects a short password and a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->artisan('crm:create-creator', ['--name' => 'A', '--email' => 'new@example.test', '--password' => 'short'])->assertFailed();
    $this->artisan('crm:create-creator', ['--name' => 'A', '--email' => 'taken@example.test', '--password' => 'secret-pass-1'])->assertFailed();

    expect(User::where('role', Role::Creator)->exists())->toBeFalse();
});
