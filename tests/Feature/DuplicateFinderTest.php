<?php

use App\Livewire\ClientForm;
use App\Models\Client;
use App\Models\User;
use App\Services\DuplicateFinder;
use Livewire\Livewire;

function found(?string $phone, ?string $email, ?int $exceptId = null): array
{
    return (new DuplicateFinder)->find($phone, $email, $exceptId)
        ->map(fn ($d) => [$d['client']->name, $d['matched']])->all();
}

it('finds by phone in different notations', function (string $phone) {
    Client::factory()->create(['name' => 'Первый', 'phone' => '+79171234567', 'email' => null]);

    expect(found($phone, null))->toBe([['Первый', ['phone']]]);
})->with(['89171234567', '8 917 123-45-67', '+7 (917) 123-45-67']);

it('finds by email ignoring case and spaces', function () {
    Client::factory()->create(['name' => 'Первый', 'phone' => null, 'email' => 'Anna@Example.test']);

    expect(found(null, '  anna@EXAMPLE.test '))->toBe([['Первый', ['email']]]);
});

it('reports both matched fields', function () {
    Client::factory()->create(['name' => 'Первый', 'phone' => '+79171234567', 'email' => 'a@example.test']);

    expect(found('89171234567', 'a@example.test'))->toBe([['Первый', ['phone', 'email']]]);
});

it('does not match empty values', function () {
    Client::factory()->create(['phone' => null, 'email' => null]);
    Client::factory()->create(['phone' => null, 'email' => '']);

    expect(found(null, null))->toBe([])
        ->and(found('', '  '))->toBe([])
        ->and(found('12345', null))->toBe([]);
});

it('ignores archived clients', function () {
    Client::factory()->archived()->create(['phone' => '+79171234567']);

    expect(found('89171234567', null))->toBe([]);
});

it('excludes the given client', function () {
    $client = Client::factory()->create(['name' => 'Сам', 'phone' => '+79171234567']);
    Client::factory()->create(['name' => 'Другой', 'phone' => '+79171234567']);

    expect(found('89171234567', null, $client->id))->toBe([['Другой', ['phone']]]);
});

describe('client form', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->create()));

    it('creates immediately when there are no matches', function () {
        Livewire::test(ClientForm::class)->set('name', 'Новый')->set('phone', '8 917 123-45-67')->call('save');

        expect(Client::count())->toBe(1);
    });

    it('warns about a duplicate instead of saving', function () {
        $existing = Client::factory()->create(['name' => 'Существующий', 'phone' => '+79171234567']);

        Livewire::test(ClientForm::class)
            ->set('name', 'Новый')->set('phone', '8 917 123-45-67')
            ->call('save')
            ->assertNoRedirect()
            ->assertSee('Возможно, такой клиент уже есть')
            ->assertSee('Существующий')
            ->assertSee('Совпало: телефон')
            ->assertDispatched('duplicates-found')
            ->assertSee(route('clients.show', $existing), false);

        expect(Client::count())->toBe(1);
    });

    it('creates a client after "continue"', function () {
        Client::factory()->create(['phone' => '+79171234567']);

        Livewire::test(ClientForm::class)
            ->set('name', 'Новый')->set('phone', '89171234567')
            ->call('save')
            ->call('save', true)
            ->assertRedirect(route('clients.show', 2));

        expect(Client::count())->toBe(2);
    });

    it('checks again when phone or email changed after the warning', function () {
        Client::factory()->create(['phone' => '+79171234567']);
        Client::factory()->create(['name' => 'Второй', 'email' => 'b@example.test']);

        Livewire::test(ClientForm::class)
            ->set('name', 'Новый')->set('phone', '89171234567')
            ->call('save')
            ->set('email', 'b@example.test')
            ->call('save', true)
            ->assertNoRedirect()
            ->assertSee('Второй');

        expect(Client::count())->toBe(2);
    });

    it('does not warn when editing', function () {
        $client = Client::factory()->create(['phone' => '+79171234567']);
        Client::factory()->create(['phone' => '+79171234567']);

        Livewire::test(ClientForm::class, ['client' => $client])
            ->set('name', 'Изменённое')
            ->call('save')
            ->assertRedirect(route('clients.show', $client));

        expect($client->fresh()->name)->toBe('Изменённое');
    });
});
