<?php

use App\Livewire\ClientTable;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function names($component): array
{
    return $component->viewData('clients')->pluck('name')->all();
}

it('searches by name', function () {
    Client::factory()->create(['name' => 'Иванов Пётр']);
    Client::factory()->create(['name' => 'Сидорова Анна']);

    Livewire::test(ClientTable::class)->set('search', 'Иванов')->assertSee('Иванов Пётр')->assertDontSee('Сидорова Анна');
});

it('searches by email, social and role', function () {
    Client::factory()->create(['name' => 'Первый', 'email' => 'first@example.test', 'social' => '@firstgram', 'role' => 'bride']);
    Client::factory()->create(['name' => 'Второй', 'email' => 'second@example.test', 'social' => null, 'role' => null]);

    foreach (['FIRST@', 'firstgram', 'BRIDE'] as $term) {
        Livewire::test(ClientTable::class)->set('search', $term)->assertSee('Первый')->assertDontSee('Второй');
    }
});

it('searches by phone in different notations', function (string $term) {
    Client::factory()->create(['name' => 'Первый', 'phone' => '8 917 123-45-67']);
    Client::factory()->create(['name' => 'Второй', 'phone' => '8 926 000-11-22']);

    Livewire::test(ClientTable::class)->set('search', $term)->assertSee('Первый')->assertDontSee('Второй');
})->with(['8 917', '+7917', '917 123', '89171234567', '+7 (917) 123-45-67']);

it('does not treat digits inside a text query as a phone search', function () {
    Client::factory()->create(['name' => 'Первый', 'phone' => '+79171234567']);

    Livewire::test(ClientTable::class)->set('search', 'Дом 7')->assertSee('Клиентов не найдено');
});

it('treats LIKE wildcards literally', function () {
    Client::factory()->create(['name' => 'Скидка 100%']);
    Client::factory()->create(['name' => 'Обычный', 'social' => 'a_b']);
    Client::factory()->create(['name' => 'Другой', 'social' => 'axb']);

    Livewire::test(ClientTable::class)->set('search', '%')->assertSee('Скидка 100%')->assertDontSee('Обычный');
    Livewire::test(ClientTable::class)->set('search', 'a_b')->assertSee('Обычный')->assertDontSee('Другой');
    Livewire::test(ClientTable::class)->set('search', '!')->assertSee('Клиентов не найдено');
});

it('filters by legal type', function () {
    Client::factory()->create(['name' => 'Частник']);
    Client::factory()->organization()->create(['name' => 'ООО Ромашка']);

    Livewire::test(ClientTable::class)->set('legalType', 'organization')->assertSee('ООО Ромашка')->assertDontSee('Частник');
    Livewire::test(ClientTable::class)->set('legalType', 'individual')->assertSee('Частник')->assertDontSee('ООО Ромашка');
});

it('sorts by name in both directions', function () {
    Client::factory()->create(['name' => 'Борис']);
    Client::factory()->create(['name' => 'Анна']);

    $component = Livewire::test(ClientTable::class)->call('sortBy', 'name')->assertSet('dir', 'asc');
    expect(names($component))->toBe(['Анна', 'Борис']);

    $component->call('sortBy', 'name')->assertSet('dir', 'desc');
    expect(names($component))->toBe(['Борис', 'Анна']);
});

it('sorts by creation date and ignores unknown columns', function () {
    Client::factory()->create(['name' => 'Старый', 'created_at' => now()->subDay()]);
    Client::factory()->create(['name' => 'Новый', 'created_at' => now()]);

    $component = Livewire::test(ClientTable::class);
    expect(names($component))->toBe(['Новый', 'Старый']);

    $component->call('sortBy', 'phone')->assertSet('sort', 'created_at');
    expect(names($component))->toBe(['Новый', 'Старый']);

    $component->call('sortBy', 'created_at');
    expect(names($component))->toBe(['Старый', 'Новый']);
});

it('hides archived clients unless asked', function () {
    Client::factory()->create(['name' => 'Активный']);
    Client::factory()->archived()->create(['name' => 'Архивный']);

    Livewire::test(ClientTable::class)->assertSee('Активный')->assertDontSee('Архивный')
        ->set('showArchived', true)->assertSee('Активный')->assertSee('Архивный');
});

it('paginates by 25', function () {
    Client::factory()->count(30)->create();

    $component = Livewire::test(ClientTable::class);
    expect($component->viewData('clients')->count())->toBe(25);

    $component->call('gotoPage', 2);
    expect($component->viewData('clients')->count())->toBe(5);
});

it('shows the empty state and the new client link', function () {
    Livewire::test(ClientTable::class)->assertSee('Клиентов не найдено')->assertSee(route('clients.create'), false);
});

it('serves the page and adds Clients to the menu', function () {
    $this->get('/clients')->assertOk()->assertSeeLivewire(ClientTable::class)->assertSee(route('clients.index'), false);
});

it('redirects guests to login', function () {
    auth()->logout();

    $this->get('/clients')->assertRedirect('/login');
});
