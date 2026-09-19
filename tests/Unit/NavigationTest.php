<?php

use App\Support\Navigation;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

function itemLabels(): array
{
    return collect(Navigation::groups())->pluck('items')->flatten(1)->pluck('label')->all();
}

it('hides items whose route does not exist and drops empty groups', function () {
    expect(itemLabels())->toBe(['Главная', 'Клиенты']);
    expect(Navigation::groups())->toHaveCount(2);
});

it('shows a group once one of its routes exists', function () {
    Route::get('/tasks', fn () => '')->name('tasks.index');
    Route::getRoutes()->refreshNameLookups();

    $groups = Navigation::groups();

    expect(itemLabels())->toBe(['Главная', 'Клиенты', 'Задачи']);
    expect($groups[2]['label'])->toBe('Работа');
});

it('marks only the current item as active', function () {
    Route::get('/counterparties', fn () => '')->name('counterparties.index');
    Route::get('/counterparties/active', fn () => '')->name('counterparties.active');
    Route::getRoutes()->refreshNameLookups();

    $this->get('/counterparties/active');

    $current = collect(Navigation::groups())->pluck('items')->flatten(1)->where('current', true)->pluck('label')->all();

    expect($current)->toBe(['Действующие контрагенты']);
});

it('leads the back arrow to the parent section', function (string $uri, ?string $expected) {
    Route::get('/clients', fn () => '')->name('clients.index');
    Route::get('/clients/create', fn () => '')->name('clients.create');
    Route::get('/clients/{id}', fn () => '')->name('clients.show');
    Route::get('/users', fn () => '')->name('users');
    Route::getRoutes()->refreshNameLookups();

    $this->get($uri);

    expect(Navigation::backUrl())->toBe($expected ? url($expected) : null);
})->with([
    'home has none' => ['/', null],
    'list goes home' => ['/clients', '/'],
    'create goes to list' => ['/clients/create', '/clients'],
    'card goes to list' => ['/clients/5', '/clients'],
    'users goes home' => ['/users', '/'],
]);
