<?php

use App\Livewire\Home;
use App\Models\User;
use Livewire\Livewire;

it('renders the home page with the Livewire component', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSeeLivewire(Home::class);
});

it('increments the counter', function () {
    Livewire::test(Home::class)->call('increment')->assertSet('count', 1);
});
