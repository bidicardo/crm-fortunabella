<?php

namespace App\Livewire;

use App\Models\Client;
use App\Services\ClientMergeService;
use DomainException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Объединение клиентов')]
class ClientMerge extends Component
{
    public const LABELS = [
        'name' => 'Имя',
        'phone' => 'Телефон',
        'email' => 'Email',
        'social' => 'Соцсеть / мессенджер',
        'legal_type' => 'Тип клиента',
        'role' => 'Роль',
        'contact_time' => 'Удобное время для связи',
        'notes' => 'Примечания',
    ];

    public Client $client;

    public string $search = '';

    /** Второй клиент; стороны в форме: «current» — открытая карточка, «other» — выбранная. */
    #[Url(as: 'with')]
    public ?int $otherId = null;

    /** Какая из двух карточек основная: current | other. */
    public string $mainSide = 'current';

    /** Выбор значения для различающихся полей: поле => current | other | both (both — только примечания). */
    public array $choices = [];

    public function mount(Client $client): void
    {
        abort_if($client->isArchived(), 403);

        $this->client = $client;

        if ($this->otherId !== null) {
            $this->pick($this->otherId);
        }
    }

    public function pick(int $id): void
    {
        $other = Client::active()->whereKeyNot($this->client->id)->find($id);

        $this->otherId = $other?->id;
        $this->mainSide = 'current';
        $this->defaultChoices($other);
    }

    /** Смена основной карточки возвращает значения по умолчанию: берётся значение основной, а если оно пустое — второй. */
    public function updatedMainSide(): void
    {
        $other = $this->otherId ? Client::active()->whereKeyNot($this->client->id)->find($this->otherId) : null;

        $this->defaultChoices($other);
    }

    private function defaultChoices(?Client $other): void
    {
        $this->choices = [];

        if (! $other) {
            return;
        }

        $preferred = $this->mainSide === 'other' ? 'other' : 'current';
        $fallback = $preferred === 'current' ? 'other' : 'current';
        $clients = ['current' => $this->client, 'other' => $other];

        foreach ($this->differingFields($other) as $field) {
            $hasPreferred = ClientMergeService::value($clients[$preferred], $field) !== null;
            $hasFallback = ClientMergeService::value($clients[$fallback], $field) !== null;

            $this->choices[$field] = $field === 'notes' && $hasPreferred && $hasFallback
                ? 'both'
                : ($hasPreferred ? $preferred : $fallback);
        }
    }

    public function back(): void
    {
        $this->otherId = null;
        $this->choices = [];
    }

    /** @return list<string> */
    private function differingFields(Client $other): array
    {
        return array_values(array_filter(
            ClientMergeService::FIELDS,
            fn (string $f) => ClientMergeService::value($this->client, $f) !== ClientMergeService::value($other, $f),
        ));
    }

    public function merge(ClientMergeService $service)
    {
        $other = $this->otherId ? Client::active()->whereKeyNot($this->client->id)->find($this->otherId) : null;

        if (! $other) {
            $this->addError('merge', 'Выберите второго клиента.');

            return;
        }

        [$main, $duplicate] = $this->mainSide === 'other' ? [$other, $this->client] : [$this->client, $other];
        $mainKey = $this->mainSide === 'other' ? 'other' : 'current';

        // «current»/«other» → «main»/«duplicate» для сервиса.
        $fieldChoices = collect($this->choices)
            ->filter(fn ($c, $f) => in_array($f, ClientMergeService::FIELDS, true) && in_array($c, ['current', 'other', 'both'], true))
            ->map(fn ($c) => $c === 'both' ? 'both' : ($c === $mainKey ? 'main' : 'duplicate'))
            ->all();

        try {
            $service->merge($main, $duplicate, $fieldChoices, auth()->user());
        } catch (DomainException $e) {
            $this->addError('merge', $e->getMessage());

            return;
        }

        session()->flash('status', "Клиенты объединены. Карточка «{$duplicate->name}» перенесена в архив.");

        return $this->redirectRoute('clients.show', $main, navigate: false);
    }

    public function render()
    {
        $other = $this->otherId ? Client::active()->whereKeyNot($this->client->id)->find($this->otherId) : null;

        $candidates = null;
        if (! $other) {
            $term = trim($this->search);
            $candidates = Client::active()
                ->whereKeyNot($this->client->id)
                ->when($term !== '', fn ($q) => $q->search($term))
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        return view('livewire.client-merge', [
            'other' => $other,
            'candidates' => $candidates,
            'differing' => $other ? $this->differingFields($other) : [],
        ]);
    }
}
