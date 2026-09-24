<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Models\Client;
use App\Services\DuplicateFinder;
use App\Services\PhoneNormalizer;
use Livewire\Component;

/** Создание клиента. Правка — по одному полю прямо в карточке (ClientShow). */
class ClientForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $social = '';

    public string $legal_type = '';

    public string $role = '';

    public string $contact_time = '';

    public string $notes = '';

    /** Найденные возможные дубли: id клиента => совпавшие поля. */
    public array $duplicates = [];

    /** Телефон и email, для которых показано предупреждение: «Продолжить» действует только для них. */
    public string $warnedFor = '';

    private function duplicatesKey(): string
    {
        return (new PhoneNormalizer)->normalize($this->phone).'|'.mb_strtolower(trim($this->email));
    }

    public function save(bool $ignoreDuplicates = false)
    {
        $data = $this->validate(Client::validationRules());

        // Создание дубля не запрещено — только предупреждение.
        if (! ($ignoreDuplicates && $this->warnedFor === $this->duplicatesKey())) {
            $found = (new DuplicateFinder)->find($data['phone'], $data['email']);

            if ($found->isNotEmpty()) {
                $this->duplicates = $found->mapWithKeys(fn ($d) => [$d['client']->id => $d['matched']])->all();
                $this->warnedFor = $this->duplicatesKey();

                // Страница прокручивается наверх, чтобы панель была видна, даже если форма была пролистана вниз.
                $this->dispatch('duplicates-found');

                return;
            }
        }

        // Пустые строки из формы храним как null.
        $client = Client::create(array_map(fn ($value) => $value === '' ? null : $value, $data));

        session()->flash('status', 'Клиент создан.');

        return $this->redirectRoute('clients.show', $client, navigate: false);
    }

    public function render()
    {
        return view('livewire.client-form', [
            'legalTypes' => ClientLegalType::cases(),
            'duplicateClients' => $this->duplicates ? Client::whereKey(array_keys($this->duplicates))->get() : collect(),
        ])->title('Новый клиент');
    }
}
