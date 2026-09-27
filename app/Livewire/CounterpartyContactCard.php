<?php

namespace App\Livewire;

use App\Livewire\Concerns\EditsFieldsInline;
use App\Models\CounterpartyContact;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/** Одно контактное лицо в карточке контрагента: правка по полю и удаление. */
class CounterpartyContactCard extends Component
{
    use EditsFieldsInline;

    public CounterpartyContact $contact;

    protected function editableModel(): Model
    {
        return $this->contact;
    }

    protected function editableRules(): array
    {
        return CounterpartyContact::validationRules();
    }

    protected function fieldSaved(): void
    {
        $this->dispatch('contact-changed');
    }

    public function delete(): void
    {
        $this->contact->delete();
        $this->dispatch('contact-changed');
        // Лицо удалено — блок уберёт карточка контрагента при перерисовке.
        $this->skipRender();
    }

    public function render()
    {
        return view('livewire.counterparty-contact-card');
    }
}
