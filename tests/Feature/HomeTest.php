<?php

use App\Livewire\Home;
use Livewire\Livewire;

it('renders the home page with the Livewire component', function () {
    $this->get('/')->assertOk()->assertSeeLivewire(Home::class);
});

it('increments the counter', function () {
    Livewire::test(Home::class)->call('increment')->assertSet('count', 1);
});
