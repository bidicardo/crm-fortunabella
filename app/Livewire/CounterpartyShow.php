<?php

namespace App\Livewire;

use App\Enums\CounterpartyStage;
use App\Livewire\Concerns\EditsFieldsInline;
use App\Livewire\Concerns\WithActivityHistory;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Services\RecordDeleter;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\On;
use Livewire\Component;

class CounterpartyShow extends Component
{
    use EditsFieldsInline, WithActivityHistory;

    public Counterparty $counterparty;

    /** Открыта ли форма «Добавить контактное лицо». */
    public bool $addingContact = false;

    /** Поля новой формы контактного лица. */
    public array $contact = [];

    protected function editableModel(): Model
    {
        return $this->counterparty;
    }

    protected function editableRules(): array
    {
        return Counterparty::validationRules();
    }

    public function changeStage(string $stage): void
    {
        $stage = CounterpartyStage::tryFrom($stage) ?? abort(404);

        if ($stage !== $this->counterparty->stage) {
            $this->counterparty->moveTo($stage);
        }
    }

    public function addContact(): void
    {
        $this->addingContact = true;
        $this->contact = array_fill_keys(array_keys(CounterpartyContact::validationRules()), '');
        $this->resetValidation();
    }

    public function cancelContact(): void
    {
        $this->addingContact = false;
        $this->contact = [];
        $this->resetValidation();
    }

    public function saveContact(): void
    {
        $rules = collect(CounterpartyContact::validationRules())->mapWithKeys(fn ($rule, $field) => ["contact.$field" => $rule])->all();
        $names = collect((new CounterpartyContact)->activityLabels())->mapWithKeys(fn ($label, $field) => ["contact.$field" => $label])->all();
        $data = $this->validate($rules, [], $names)['contact'];

        $this->counterparty->contacts()->create(array_map(fn ($value) => $value === '' ? null : $value, $data));

        $this->cancelContact();
    }

    /** Окончательное удаление вместе с контактными лицами и историей (подтверждение — wire:confirm на кнопке). */
    public function destroy(RecordDeleter $deleter)
    {
        try {
            $deleter->delete($this->counterparty);
        } catch (DomainException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        session()->flash('status', 'Контрагент удалён.');

        // Список контрагентов появится в задаче 23; до него — главная.
        return $this->redirect(Route::has('counterparties.index') ? route('counterparties.index') : route('home'), navigate: false);
    }

    // Контактное лицо изменено или удалено в своём компоненте — перерисовываем список и историю.
    #[On('contact-changed')]
    public function refreshContacts(): void {}

    public function render()
    {
        return view('livewire.counterparty-show', [
            ...$this->historyData($this->counterparty),
            'contacts' => $this->counterparty->contacts()->get(),
            'stages' => CounterpartyStage::cases(),
        ])->title('Контрагент');
    }
}
