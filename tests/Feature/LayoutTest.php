<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('shows only existing sections in the menu', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertOk()
        ->assertSee('aria-label="Основное меню"', false)
        ->assertSee('Главная')
        ->assertSee('Клиенты')
        ->assertDontSee('Сделки');
});

it('shows a menu item when its route appears', function () {
    Route::middleware('web')->get('/deals', fn () => 'ok')->name('deals.index');
    Route::getRoutes()->refreshNameLookups();

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('Сделки')
        ->assertSee('Продажи');
});

it('marks the active item with aria-current', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('aria-current="page"', false);
});

it('shows the section title in the top bar and in the page title', function () {
    $response = $this->actingAs(User::factory()->creator()->create())->get('/users')
        ->assertSee('<title>Пользователи — '.config('app.name').'</title>', false);

    expect($response->getContent())->toMatch('~<span id="page-title"[^>]*>Пользователи</span>~u');
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

function addRoutes(string ...$names): void
{
    foreach ($names as $name) {
        Route::middleware('web')->get('/'.str_replace('.', '-', $name), fn () => Blade::render('<x-layouts::app>x</x-layouts::app>'))->name($name);
    }
    Route::getRoutes()->refreshNameLookups();
}

it('has a bottom bar with Home and no tabs for missing routes', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('aria-label="Нижнее меню"', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertDontSee('Ещё')
        ->assertDontSee('Задачи');
});

it('shows tabs and More when routes appear', function () {
    addRoutes('deals.index', 'tasks.index', 'calendar.index', 'documents.index');

    $this->actingAs(User::factory()->create())->get('/')
        ->assertSee('Сделки')
        ->assertSee('Клиенты')
        ->assertSee('Задачи')
        ->assertSee('Ещё')
        ->assertSee('Календарь')
        ->assertSee('Документы');
});

it('hides More when there are no extra sections', function () {
    addRoutes('deals.index', 'tasks.index');

    $this->actingAs(User::factory()->create())->get('/')->assertDontSee('Ещё');
});

it('marks the active tab in the bottom bar', function () {
    addRoutes('deals.index');

    $html = $this->actingAs(User::factory()->create())->get('/deals-index')->getContent();
    $bottom = substr($html, strpos($html, 'aria-label="Нижнее меню"'));

    expect($bottom)->toContain('aria-current="page"');
});

it('does not show the bottom bar on guest pages', function () {
    $this->get('/login')->assertDontSee('Нижнее меню');
    $this->get('/invite/bad-token')->assertDontSee('Нижнее меню');
});

it('does not duplicate Users in More', function () {
    addRoutes('calendar.index');

    $html = $this->actingAs(User::factory()->creator()->create())->get('/')->getContent();
    $bottom = substr($html, strpos($html, 'aria-label="Нижнее меню"'));

    expect($bottom)->toContain('Календарь')->not->toContain(route('users'));
});

it('shows the back arrow to the parent section, and not on the home page', function () {
    $user = User::factory()->creator()->create();

    $this->actingAs($user)->get('/')->assertDontSee('aria-label="Назад"', false);
    $this->actingAs($user)->get('/users')->assertSee('aria-label="Назад"', false);
    $html = $this->actingAs($user)->get('/clients/create')->getContent();

    expect($html)->toMatch('~href="'.preg_quote(route('clients.index'), '~').'"\s+aria-label="Назад"~u');
});
