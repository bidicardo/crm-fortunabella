<?php

use App\Livewire\ClientForm;
use App\Livewire\ClientShow;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

it('edits a client field by field right on the card', function () {
    $client = Client::factory()->create(['name' => 'Старое имя', 'phone' => '+79171234567']);

    Livewire::test(ClientShow::class, ['client' => $client])
        ->call('edit', 'name')
        ->assertSet('editing', 'name')
        ->assertSet('value', 'Старое имя')
        ->set('value', 'Новое имя')
        ->call('save')
        ->assertSet('editing', null)
        ->call('edit', 'phone')
        ->set('value', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($client->fresh())->name->toBe('Новое имя')->phone->toBeNull();
});

it('keeps the field open with an error when the value is invalid', function () {
    $client = Client::factory()->create(['name' => 'Имя', 'phone' => '+79171234567']);

    Livewire::test(ClientShow::class, ['client' => $client])
        ->call('edit', 'phone')
        ->set('value', '123')
        ->call('save')
        ->assertHasErrors('value')
        ->assertSet('editing', 'phone')
        ->call('edit', 'name')          // другой щелчок не бросает поле с ошибкой
        ->assertSet('editing', 'phone')
        ->call('cancel')
        ->assertSet('editing', null)
        ->assertHasNoErrors();

    expect($client->fresh()->phone)->toBe('+79171234567');
});

it('saves the open field when another field is clicked', function () {
    $client = Client::factory()->create(['role' => null]);

    Livewire::test(ClientShow::class, ['client' => $client])
        ->call('edit', 'role')
        ->set('value', 'Организатор')
        ->call('edit', 'notes')
        ->assertSet('editing', 'notes');

    expect($client->fresh()->role)->toBe('Организатор');
});

it('stores an empty legal type as null and rejects unknown fields', function () {
    $client = Client::factory()->create();

    Livewire::test(ClientShow::class, ['client' => $client])
        ->call('edit', 'legal_type')->set('value', '')->call('save')->assertHasNoErrors();

    expect($client->fresh()->legal_type)->toBeNull();

    Livewire::test(ClientShow::class, ['client' => $client])->call('edit', 'archived_at')->assertNotFound();
});

it('has no separate edit page', function () {
    $client = Client::factory()->create();

    expect(Route::has('clients.edit'))->toBeFalse();
    $this->get("/clients/{$client->id}/edit")->assertNotFound();
});

it('shows the client card', function () {
    $client = Client::factory()->create(['name' => 'Мария Тестова']);

    $this->get(route('clients.show', $client))->assertOk()->assertSee('Мария Тестова')->assertSee('Изменить');
});

it('returns 404 for a missing client', function () {
    $this->get('/clients/999')->assertNotFound();
});

it('redirects guests to login', function () {
    auth()->logout();

    $this->get('/clients/create')->assertRedirect('/login');
});

it('keeps archived clients read-only', function () {
    $client = Client::factory()->archived()->create();

    $this->get(route('clients.show', $client))->assertOk()->assertSee('Карточка архивирована')->assertDontSee('Изменить');
    Livewire::test(ClientShow::class, ['client' => $client])->call('edit', 'name')->assertForbidden();
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

it('shows the input with save and cancel for the field being edited', function () {
    $client = Client::factory()->create(['notes' => "строка 1\nстрока 2"]);

    Livewire::test(ClientShow::class, ['client' => $client])
        ->call('edit', 'legal_type')
        ->assertSeeHtml('id="edit-legal_type"')
        ->assertSeeHtml('<option value="organization">')
        ->assertSee('Сохранить')->assertSee('Отмена')
        ->call('edit', 'notes')
        ->assertSeeHtml('id="edit-notes"')
        ->assertDontSeeHtml('id="edit-legal_type"');
});
