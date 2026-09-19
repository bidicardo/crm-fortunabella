<?php

use App\Enums\ClientLegalType;
use App\Models\Client;

it('creates a client with only a name', function () {
    $client = Client::create(['name' => 'Тестовый Клиент']);

    expect($client->fresh())
        ->name->toBe('Тестовый Клиент')
        ->phone->toBeNull()
        ->email->toBeNull()
        ->legal_type->toBeNull()
        ->role->toBeNull()
        ->archived_at->toBeNull();
});

it('casts legal type to an enum and keeps role as free text', function () {
    $client = Client::factory()->organization()->create(['role' => 'Свадебный организатор из агентства'])->fresh();

    expect($client->legal_type)->toBe(ClientLegalType::Organization)
        ->and($client->legal_type->label())->toBe('Организация')
        ->and($client->role)->toBe('Свадебный организатор из агентства');
});

it('scopes active clients', function () {
    $active = Client::factory()->create();
    Client::factory()->archived()->create();

    expect(Client::active()->pluck('id')->all())->toBe([$active->id]);
});

it('knows whether it is archived', function () {
    expect(Client::factory()->create()->isArchived())->toBeFalse()
        ->and(Client::factory()->archived()->create()->isArchived())->toBeTrue();
});

it('does not allow mass assignment of archived_at', function () {
    $client = Client::create(['name' => 'Иван', 'archived_at' => now()]);

    expect($client->fresh()->isArchived())->toBeFalse();
});

it('allows clients with the same phone and email', function () {
    Client::factory()->count(2)->create(['phone' => '+79001112233', 'email' => 'same@example.test']);

    expect(Client::where('phone', '+79001112233')->count())->toBe(2);
});

it('generates phones in the +7XXXXXXXXXX format', function () {
    expect(Client::factory()->make()->phone)->toMatch('/^\+7\d{10}$/');
});
