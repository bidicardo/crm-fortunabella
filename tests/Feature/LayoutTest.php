<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('shows only existing sections in the menu', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('aria-label="Основное меню"', false)
        ->assertSee('Главная')
        ->assertDontSee('Сделки')
        ->assertDontSee('Клиенты');
});

it('shows a menu item when its route appears', function () {
    Route::middleware('web')->get('/clients', fn () => 'ok')->name('clients.index');
    Route::getRoutes()->refreshNameLookups();

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('Клиенты')
        ->assertSee('Продажи');
});

it('marks the active item with aria-current', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('aria-current="page"', false);
});

it('shows the section title in the top bar and in the page title', function () {
    $this->actingAs(User::factory()->creator()->create())->get('/users')
        ->assertSee('<span class="truncate text-lg font-semibold">Пользователи</span>', false)
        ->assertSee('<title>Пользователи — '.config('app.name').'</title>', false);
});

it('shows Users in the user menu only to the creator', function () {
    $this->actingAs(User::factory()->creator()->create())->get('/')->assertSee(route('users'), false);
    $this->actingAs(User::factory()->create())->get('/')->assertDontSee(route('users'), false);
});

it('has a logout form', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Выйти');
});

it('does not show the sidebar on login and invite pages', function () {
    $this->get('/login')->assertOk()->assertDontSee('Основное меню')->assertSee('Тёмная тема', false);
    $this->get('/invite/bad-token')->assertNotFound()->assertDontSee('Основное меню');
});
