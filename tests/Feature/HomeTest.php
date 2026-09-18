<?php

namespace Tests\Feature;

use App\Livewire\Home;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_renders_livewire_component(): void
    {
        $this->get('/')->assertOk()->assertSeeLivewire(Home::class);
    }

    public function test_counter_increments(): void
    {
        Livewire::test(Home::class)->call('increment')->assertSet('count', 1);
    }
}
