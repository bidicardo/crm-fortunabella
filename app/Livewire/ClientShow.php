<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Livewire\Concerns\EditsFieldsInline;
use App\Livewire\Concerns\WithActivityHistory;
use App\Models\Client;
use App\Services\RecordDeleter;
use DomainException;
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

    /** Окончательное удаление вместе с влитыми дублями и историей (подтверждение — wire:confirm на кнопке). */
    public function destroy(RecordDeleter $deleter)
    {
        // Архивный дубль отдельно не удаляется — только вместе с основной карточкой.
        abort_if($this->client->isArchived(), 403);

        try {
            $deleter->delete($this->client);
        } catch (DomainException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        session()->flash('status', 'Клиент удалён.');

        return $this->redirectRoute('clients.index', navigate: false);
    }

    public function render()
    {
        $this->client->loadMissing('mergedInto', 'mergedBy');

        return view('livewire.client-show', [
            ...$this->historyData($this->client),
            'duplicatesCount' => $this->client->isArchived() ? 0 : count(app(RecordDeleter::class)->duplicateIds($this->client)),
            'legalTypes' => ClientLegalType::cases(),
        ])->title('Клиент');
    }
}
