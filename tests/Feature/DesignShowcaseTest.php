<?php

use App\Models\User;

it('opens only when the environment is local', function () {
    config(['app.env' => 'local']);
    $this->get('/design-showcase')->assertOk()->assertSee('Витрина дизайн-системы');
});

it('answers 404 outside the local environment', function () {
    config(['app.env' => 'production']);
    $this->get('/design-showcase')->assertNotFound();

    config(['app.env' => 'testing']);
    $this->get('/design-showcase')->assertNotFound();
});

it('is not in the menu', function () {
    config(['app.env' => 'local']);

    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertDontSee(route('design-showcase'), false);
});
