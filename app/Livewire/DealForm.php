<?php

namespace App\Livewire;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\TableType;
use App\Livewire\Concerns\EditsDealParties;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\Deal;
use App\Rules\RussianPhone;
use App\Services\DuplicateFinder;
use App\Services\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Создание сделки (в компоновке карточки DealShow). Обязательны название и клиент: выбранный поиском или
 * новый — прямо здесь (имя и телефон, с предупреждением о возможном дубле, как в ClientForm).
 * ?stage=, ?client=, ?counterparty= — предвыбор (кнопка «+» канбана, кнопки в карточках клиента и контрагента).
 */
class DealForm extends Component
{
    use EditsDealParties;

    public string $title = '';

    public ?int $client_id = null;

    public ?int $counterparty_id = null;

    public string $amount = '';

    public string $event_date = '';

    public string $event_time = '';

    public array $table_types = [];

    public string $duration_hours = '';

    public string $event_kind = '';

    public string $guests = '';

    public string $address = '';

    public string $lead_source = '';

    public string $lead_source_other = '';

    public string $prepayment_amount = '';

    public bool $prepayment_paid = false;

    public string $prepayment_holder_id = '';

    public string $sold_by_id = '';

    public string $notes = '';

    public string $stage = '';

    /** Открыта ли форма нового клиента вместо поиска. */
    public bool $newClient = false;

    public string $newClientName = '';

    public string $newClientPhone = '';

    /** id найденных возможных дублей нового клиента. */
    public array $duplicates = [];

    /** Телефон, для которого показано предупреждение: «Всё равно создать» действует только для него. */
    public string $warnedFor = '';

    public function mount(): void
    {
        $this->stage = (DealStage::tryFrom((string) request()->query('stage')) ?? DealStage::New)->value;
        $this->client_id = Client::active()->whereKey((int) request()->query('client'))->value('id');
        $this->counterparty_id = Counterparty::whereKey((int) request()->query('counterparty'))->value('id');
        $this->sold_by_id = (string) auth()->id();
    }

    public function pickClient(int $id): void
    {
        $this->client_id = Client::active()->whereKey($id)->value('id');
        $this->clientSearch = '';
        $this->newClient = false;
        $this->duplicates = [];
    }

    public function clearClient(): void
    {
        $this->client_id = null;
    }

    public function pickCounterparty(int $id): void
    {
        $this->counterparty_id = Counterparty::whereKey($id)->value('id');
        $this->counterpartySearch = '';
    }

    public function clearCounterparty(): void
    {
        $this->counterparty_id = null;
    }

    public function startNewClient(): void
    {
        $this->newClient = true;
        $this->client_id = null;
        $this->newClientName = trim($this->clientSearch);
        $this->clientSearch = '';
    }

    public function cancelNewClient(): void
    {
        $this->newClient = false;
        $this->duplicates = [];
    }

    public function updatedDurationChoice(): void
    {
        $this->duration_hours = $this->durationChoice === 'other' ? '' : $this->durationChoice;
    }

    public function save(bool $ignoreDuplicates = false)
    {
        $rules = [...Deal::validationRules(), 'stage' => ['required', Rule::enum(DealStage::class)]];

        if ($this->newClient) {
            unset($rules['client_id']);
            $rules['newClientName'] = ['required', 'string', 'max:255'];
            $rules['newClientPhone'] = ['nullable', new RussianPhone];
        }

        $data = $this->validate($rules, [], [
            'client_id' => 'клиент',
            'newClientName' => 'имя клиента',
            'newClientPhone' => 'телефон клиента',
            'duration_hours' => 'длительность',
        ]);

        // Новый клиент: при совпадении телефона — предупреждение (создание не запрещено, как в ClientForm).
        if ($this->newClient) {
            $phone = (new PhoneNormalizer)->normalize($this->newClientPhone) ?? '';

            if (! ($ignoreDuplicates && $this->warnedFor === $phone)) {
                $found = (new DuplicateFinder)->find($phone, null);

                if ($found->isNotEmpty()) {
                    $this->duplicates = $found->pluck('client.id')->all();
                    $this->warnedFor = $phone;
                    $this->dispatch('duplicates-found');

                    return;
                }
            }
        }

        $deal = DB::transaction(function () use ($data) {
            if ($this->newClient) {
                $data['client_id'] = Client::create(['name' => $data['newClientName'], 'phone' => $data['newClientPhone'] ?: null])->id;
            }

            // Пустые строки храним как null; этап не присваивается массово — задаётся отдельно.
            $fields = collect($data)->only((new Deal)->getFillable())->map(fn ($value) => $value === '' ? null : $value)->all();
            $deal = new Deal($fields);
            $deal->forceFill(['stage' => $data['stage']])->save();

            return $deal;
        });

        session()->flash('status', 'Сделка создана.');

        return $this->redirectRoute('deals.show', $deal, navigate: false);
    }

    public function render()
    {
        return view('livewire.deal-form', [
            ...$this->partyData(),
            'client' => $this->client_id ? Client::find($this->client_id) : null,
            'counterparty' => $this->counterparty_id ? Counterparty::find($this->counterparty_id) : null,
            'duplicateClients' => $this->duplicates ? Client::whereKey($this->duplicates)->get() : collect(),
            'sameDay' => $this->stage === DealStage::Refused->value ? collect() : Deal::othersOn($this->event_date),
            'stages' => DealStage::cases(),
            'sources' => LeadSource::cases(),
            'tableTypes' => TableType::cases(),
        ])->title('Новая сделка');
    }
}
