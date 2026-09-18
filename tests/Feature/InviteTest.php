<?php

use App\Models\Invite;
use App\Models\User;

function inviteWithToken(string $token): Invite
{
    return Invite::factory()->create(['token_hash' => hash('sha256', $token)]);
}

it('issues an invite and never stores the plain token', function () {
    $this->freezeTime();
    $creator = User::factory()->creator()->create();

    [$invite, $token] = Invite::issue($creator);

    expect(strlen($token))->toBeGreaterThanOrEqual(40)
        ->and($invite->created_by)->toBe($creator->id)
        ->and($invite->token_hash)->toBe(hash('sha256', $token))
        ->and($invite->token_hash)->not->toBe($token)
        ->and($invite->expires_at->timestamp)->toBe(now()->addHours(24)->timestamp);

    $this->assertDatabaseMissing('invites', ['token_hash' => $token]);
});

it('issues a different token every time', function () {
    $creator = User::factory()->creator()->create();

    expect(Invite::issue($creator)[1])->not->toBe(Invite::issue($creator)[1]);
});

it('finds a valid invite by its token', function () {
    $invite = inviteWithToken('known-token');

    expect(Invite::findValid('known-token')->is($invite))->toBeTrue();
});

it('does not find the invite by its stored hash', function () {
    inviteWithToken('known-token');

    expect(Invite::findValid(hash('sha256', 'known-token')))->toBeNull();
});

it('does not find unknown, used or expired invites', function () {
    Invite::factory()->used()->create(['token_hash' => hash('sha256', 'used-token')]);
    Invite::factory()->expired()->create(['token_hash' => hash('sha256', 'expired-token')]);

    expect(Invite::findValid('unknown-token'))->toBeNull()
        ->and(Invite::findValid('used-token'))->toBeNull()
        ->and(Invite::findValid('expired-token'))->toBeNull();
});

it('expires exactly after 24 hours', function () {
    [, $token] = Invite::issue(User::factory()->creator()->create());

    $this->travel(23)->hours();
    expect(Invite::findValid($token))->not->toBeNull();

    $this->travel(1)->hours();
    expect(Invite::findValid($token))->toBeNull();
});

it('can be marked used only once', function () {
    $invite = inviteWithToken('known-token');
    $stale = Invite::find($invite->id); // второй «параллельный» запрос уже загрузил приглашение неиспользованным
    $first = User::factory()->create();
    $second = User::factory()->create();

    expect($invite->markUsed($first))->toBeTrue()
        ->and($invite->used_at)->not->toBeNull()
        ->and($invite->user_id)->toBe($first->id)
        ->and(Invite::findValid('known-token'))->toBeNull();

    expect($stale->markUsed($second))->toBeFalse()
        ->and($invite->fresh()->user_id)->toBe($first->id);
});

it('cannot be marked used after it expired', function () {
    $invite = Invite::factory()->expired()->create();

    expect($invite->markUsed(User::factory()->create()))->toBeFalse()
        ->and($invite->fresh()->used_at)->toBeNull();
});
