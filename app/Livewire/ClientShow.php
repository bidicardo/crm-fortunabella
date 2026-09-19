<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;

class ClientShow extends Component
{
    public Client $client;

    public function render()
    {
        return view('livewire.client-show')->title($this->client->name);
    }
}
