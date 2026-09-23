<?php

use App\Livewire\ClientForm;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('creates a client with only a name', function () {
    Livewire::test(ClientForm::class)->set('name', 'Тестовый Клиент')->call('save')->assertHasNoErrors();

    $client = Client::firstOrFail();
    expect($client->name)->toBe('Тестовый Клиент')->and($client->phone)->toBeNull();
});

it('creates a client with all fields and normalizes the phone', function () {
    Livewire::test(ClientForm::class)
        ->set('name', 'Анна')
        ->set('phone', '8 917 123-45-67')
        ->set('email', 'anna@example.test')
        ->set('social', '@anna')
        ->set('legal_type', 'individual')
        ->set('role', 'невеста')
        ->set('contact_time', 'после 18:00')
        ->set('notes', "строка 1\nстрока 2")
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::firstOrFail();
    expect($client->phone)->toBe('+79171234567')
        ->and($client->legal_type->value)->toBe('individual')
        ->and($client->role)->toBe('невеста')
        ->and($client->notes)->toBe("строка 1\nстрока 2");
});

it('redirects to the card after saving', function () {
    Livewire::test(ClientForm::class)
        ->set('name', 'Анна')
        ->call('save')
        ->assertRedirect(route('clients.show', 1));
});

it('validates name, phone and email', function () {
    Livewire::test(ClientForm::class)->call('save')->assertHasErrors(['name' => 'required']);

    Livewire::test(ClientForm::class)
        ->set('name', 'Иван')->set('phone', '12345')->set('email', 'не-email')
        ->call('save')
        ->assertHasErrors(['phone', 'email']);

    expect(Client::count())->toBe(0);
});

it('edits a client', function () {
    $client = Client::factory()->create(['name' => 'Старое имя']);

    Livewire::test(ClientForm::class, ['client' => $client])
        ->assertSet('name', 'Старое имя')
        ->set('name', 'Новое имя')
        ->set('phone', '')
        ->call('save')
        ->assertRedirect(route('clients.show', $client));

    expect($client->fresh())->name->toBe('Новое имя')->phone->toBeNull();
});

it('shows the client card', function () {
    $client = Client::factory()->create(['name' => 'Мария Тестова']);

    $this->get(route('clients.show', $client))->assertOk()->assertSee('Мария Тестова')->assertSee('Редактировать');
});

it('returns 404 for a missing client', function () {
    $this->get('/clients/999')->assertNotFound();
    $this->get('/clients/999/edit')->assertNotFound();
});

it('redirects guests to login', function () {
    auth()->logout();

    $this->get('/clients/create')->assertRedirect('/login');
});

it('keeps archived clients read-only', function () {
    $client = Client::factory()->archived()->create();

    $this->get(route('clients.show', $client))->assertOk()->assertSee('Карточка архивирована')->assertDontSee('Редактировать');
    $this->get(route('clients.edit', $client))->assertForbidden();
});

it('escapes the name on the card', function () {
    $client = Client::factory()->create(['name' => '<script>alert(1)</script>']);

    $this->get(route('clients.show', $client))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('titles the card "Клиент" in the top bar and shows the name only in the heading', function () {
    $client = Client::factory()->create(['name' => 'Иван Олегович']);

    $response = $this->get(route('clients.show', $client))
        ->assertSee('<title>Клиент — '.config('app.name').'</title>', false)
        ->assertSee('Иван Олегович');

    expect($response->getContent())->toMatch('~<span id="page-title"[^>]*>Клиент</span>~u');
});
