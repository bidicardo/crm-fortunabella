<?php

namespace App\Livewire;

use App\Enums\CounterpartyStage;
use App\Models\Counterparty;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Создание контрагента. Правка — по одному полю прямо в карточке (CounterpartyShow). */
class CounterpartyForm extends Component
{
    public string $name = '';

    public string $type = '';

    public string $phone = '';

    public string $email = '';

    public string $social = '';

    public string $cooperation_terms = '';

    public string $cooperation_started_at = '';

    public string $website = '';

    public string $telegram = '';

    public string $address = '';

    public string $stage = '';

    // ?stage= задаёт начальный этап (кнопка «+» в колонке канбана); неверное значение — «Первый контакт».
    public function mount(): void
    {
        $this->stage = (CounterpartyStage::tryFrom((string) request()->query('stage')) ?? CounterpartyStage::FirstContact)->value;
    }

    public function save()
    {
        $data = $this->validate([...Counterparty::validationRules(), 'stage' => ['required', Rule::enum(CounterpartyStage::class)]]);

        // Пустые строки из формы храним как null; этап не присваивается массово — задаётся отдельно.
        $counterparty = new Counterparty(array_map(fn ($value) => $value === '' ? null : $value, $data));
        $counterparty->forceFill(['stage' => $data['stage']])->save();

        session()->flash('status', 'Контрагент создан.');

        return $this->redirectRoute('counterparties.show', $counterparty, navigate: false);
    }

    public function render()
    {
        return view('livewire.counterparty-form', ['stages' => CounterpartyStage::cases()])->title('Новый контрагент');
    }
}
