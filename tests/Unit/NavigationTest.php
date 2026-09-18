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
    expect(itemLabels())->toBe(['Главная']);
    expect(Navigation::groups())->toHaveCount(1);
});

it('shows a group once one of its routes exists', function () {
    Route::get('/clients', fn () => '')->name('clients.index');
    Route::getRoutes()->refreshNameLookups();

    $groups = Navigation::groups();

    expect(itemLabels())->toBe(['Главная', 'Клиенты']);
    expect($groups[1]['label'])->toBe('Продажи');
});

it('marks only the current item as active', function () {
    Route::get('/counterparties', fn () => '')->name('counterparties.index');
    Route::get('/counterparties/active', fn () => '')->name('counterparties.active');
    Route::getRoutes()->refreshNameLookups();

    $this->get('/counterparties/active');

    $current = collect(Navigation::groups())->pluck('items')->flatten(1)->where('current', true)->pluck('label')->all();

    expect($current)->toBe(['Действующие контрагенты']);
});
