<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Models\Client;
use App\Rules\RussianPhone;
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

    public function save()
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
        ])->title($this->client ? 'Редактирование клиента' : 'Новый клиент');
    }
}
