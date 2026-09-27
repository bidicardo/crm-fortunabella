<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Livewire\Concerns\EditsFieldsInline;
use App\Livewire\Concerns\WithActivityHistory;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ClientShow extends Component
{
    use EditsFieldsInline, WithActivityHistory;

    public Client $client;

    protected function editableModel(): Model
    {
        return $this->client;
    }

    protected function editableRules(): array
    {
        return Client::validationRules();
    }

    // Архивная карточка (влитый дубль) — только для чтения.
    protected function editableReadonly(): bool
    {
        return $this->client->isArchived();
    }

    public function render()
    {
        $this->client->loadMissing('mergedInto', 'mergedBy');

        return view('livewire.client-show', [
            ...$this->historyData($this->client),
            'legalTypes' => ClientLegalType::cases(),
        ])->title('Клиент');
    }
}
