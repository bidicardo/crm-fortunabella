<?php

namespace App\Livewire;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\TableType;
use App\Livewire\Concerns\EditsDealParties;
use App\Livewire\Concerns\EditsFieldsInline;
use App\Livewire\Concerns\WithActivityHistory;
use App\Models\Deal;
use App\Services\RecordDeleter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Карточка сделки по референсу 07-deal-card: слева основное, справа линия этапов (нажатие меняет этап) и
 * вкладки. Правка — по одному полю (EditsFieldsInline); типы столов и «предоплата оплачена» — своими методами.
 */
class DealShow extends Component
{
    use EditsDealParties, EditsFieldsInline, WithActivityHistory;

    public const TABS = ['details' => 'Детали', 'notes' => 'Примечания', 'history' => 'История', 'tasks' => 'Задачи', 'documents' => 'Документы'];

    public Deal $deal;

    #[Url]
    public string $tab = 'details';

    /** Черновик типов столов (множественный выбор правится отдельно от $value). */
    public array $tableTypes = [];

    public bool $editingTableTypes = false;

    /** Предупреждение после перехода в «Бронь» без предоплаты — у линии этапов, закрывается. */
    public string $bookingWarning = '';

    protected function editableModel(): Model
    {
        return $this->deal;
    }

    // Все поля сделки, кроме тех, что правятся своими методами (массив типов столов, флажок оплаты).
    protected function editableRules(): array
    {
        return Arr::except(Deal::validationRules(), ['table_types', 'table_types.*', 'prepayment_paid']);
    }

    protected function fieldSaved(): void
    {
        $this->clientSearch = '';
        $this->counterpartySearch = '';
    }

    public function mount(): void
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'details';
        }
    }

    public function changeStage(string $stage): void
    {
        $stage = DealStage::tryFrom($stage) ?? abort(404);

        if ($stage !== $this->deal->stage) {
            $this->deal->moveTo($stage);
            $this->bookingWarning = $this->deal->bookingWarning() ?? '';
        }
    }

    public function editDuration(): void
    {
        $this->edit('duration_hours');
        $this->durationChoice = self::durationChoiceFor($this->deal->duration_hours);
    }

    // Выбор 1–10 сохраняется сразу; «Другое» — поле для числа, сохранение по Enter или ✓.
    public function updatedDurationChoice(): void
    {
        if ($this->editing !== 'duration_hours') {
            return;
        }

        if ($this->durationChoice === 'other') {
            $this->value = '';

            return;
        }

        $this->value = $this->durationChoice;
        $this->save();
    }

    public function pickClient(int $id): void
    {
        abort_unless($this->editing === 'client_id', 404);
        $this->value = (string) $id;
        $this->save();
    }

    public function pickCounterparty(int $id): void
    {
        abort_unless($this->editing === 'counterparty_id', 404);
        $this->value = (string) $id;
        $this->save();
    }

    public function clearCounterparty(): void
    {
        abort_unless($this->editing === 'counterparty_id', 404);
        $this->value = '';
        $this->save();
    }

    public function editTableTypes(): void
    {
        $this->cancel();
        $this->tableTypes = $this->deal->table_types ?? [];
        $this->editingTableTypes = true;
    }

    public function saveTableTypes(): void
    {
        $this->validate(['tableTypes' => ['array'], 'tableTypes.*' => [Rule::enum(TableType::class)]]);

        // Порядок — как в справочнике, чтобы история не писала перестановку как изменение.
        $types = array_values(array_intersect(array_column(TableType::cases(), 'value'), $this->tableTypes));
        $this->deal->update(['table_types' => $types ?: null]);
        $this->editingTableTypes = false;
    }

    public function cancelTableTypes(): void
    {
        $this->editingTableTypes = false;
    }

    public function togglePrepaymentPaid(): void
    {
        $this->deal->update(['prepayment_paid' => ! $this->deal->prepayment_paid]);
    }

    /** Окончательное удаление вместе с историей (подтверждение — wire:confirm на кнопке). */
    public function destroy(RecordDeleter $deleter)
    {
        $deleter->delete($this->deal);

        session()->flash('status', 'Сделка удалена.');

        // Канбан сделок появится в задаче 28; до него — главная.
        return $this->redirect(Route::has('deals.index') ? route('deals.index') : route('home'), navigate: false);
    }

    public function render()
    {
        $this->deal->load(['client', 'counterparty', 'soldBy', 'prepaymentHolder']);

        return view('livewire.deal-show', [
            ...$this->historyData($this->deal),
            ...$this->partyData(),
            'stages' => DealStage::cases(),
            'sources' => LeadSource::cases(),
            'tableTypeOptions' => TableType::cases(),
            'tabs' => self::TABS,
            // Занятая дата: другие сделки в тот же день (у сделки в «Отказе» предупреждения нет).
            'sameDay' => $this->deal->stage === DealStage::Refused ? collect() : Deal::othersOn($this->deal->event_date, $this->deal->id),
        ])->title('Сделка');
    }
}
