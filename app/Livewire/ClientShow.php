<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;

class ClientShow extends Component
{
    public Client $client;

    public function render()
    {
        $this->client->loadMissing('mergedInto', 'mergedBy');

        return view('livewire.client-show')->title('Клиент');
    }
}
