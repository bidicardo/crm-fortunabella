<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Models\Client;
use App\Rules\RussianPhone;
use App\Services\DuplicateFinder;
use App\Services\PhoneNormalizer;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ClientForm extends Component
{
    public ?Client $client = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $social = '';

    public string $legal_type = '';

    public string $role = '';

    public string $contact_time = '';

    public string $notes = '';

    public function mount(?Client $client = null): void
    {
        if (! $client?->exists) {
            return;
        }

        // Архивные карточки только для чтения: страница редактирования отдаёт 403.
        abort_if($client->isArchived(), 403);

        $this->client = $client;
        $this->fill(array_map(fn ($v) => $v ?? '', $client->only(['name', 'phone', 'email', 'social', 'role', 'contact_time', 'notes'])));
        $this->legal_type = $client->legal_type?->value ?? '';
    }

    /** Найденные при создании возможные дубли: id клиента => совпавшие поля. */
    public array $duplicates = [];

    /** Телефон и email, для которых показано предупреждение: «Продолжить» действует только для них. */
    public string $warnedFor = '';

    private function duplicatesKey(): string
    {
        return (new PhoneNormalizer)->normalize($this->phone).'|'.mb_strtolower(trim($this->email));
    }

    public function save(bool $ignoreDuplicates = false)
    {
        abort_if($this->client?->isArchived(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', new RussianPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'social' => ['nullable', 'string', 'max:255'],
            'legal_type' => ['nullable', Rule::enum(ClientLegalType::class)],
            'role' => ['nullable', 'string', 'max:255'],
            'contact_time' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        // Предупреждение о дубле — только при создании; создание дубля не запрещено.
        if (! $this->client && ! ($ignoreDuplicates && $this->warnedFor === $this->duplicatesKey())) {
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
        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

        $client = $this->client ?? new Client;
        $client->fill($data)->save();

        session()->flash('status', $this->client ? 'Изменения сохранены.' : 'Клиент создан.');

        return $this->redirectRoute('clients.show', $client, navigate: false);
    }

    public function render()
    {
        return view('livewire.client-form', [
            'legalTypes' => ClientLegalType::cases(),
            'duplicateClients' => $this->duplicates ? Client::whereKey(array_keys($this->duplicates))->get() : collect(),
        ])->title($this->client ? 'Редактирование клиента' : 'Новый клиент');
    }
}
