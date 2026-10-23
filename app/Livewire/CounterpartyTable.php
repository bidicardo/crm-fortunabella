<?php

namespace App\Livewire;

use App\Enums\CounterpartyStage;
use App\Enums\DealStage;
use App\Livewire\Concerns\DeletesSelected;
use App\Models\Counterparty;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Таблица контрагентов: «База контрагентов» (counterparties.list, вид «Список» раздела) и «Действующие
 * контрагенты» (counterparties.active — тот же компонент, $active из маршрута: этап «Сотрудничаем»
 * зафиксирован, без фильтра и колонки этапа).
 */
class CounterpartyTable extends Component
{
    use DeletesSelected, WithPagination;

    private const SORTABLE = ['name', 'cooperation_started_at', 'done_deals_count', 'created_at', 'updated_at'];

    #[Locked]
    public bool $active = false;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $stage = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $dir = 'desc';

    public function mount(bool $active = false): void
    {
        $this->active = $active;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    protected function deletableQuery(): Builder
    {
        return Counterparty::query();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        $this->dir = $this->sort === $column && $this->dir === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    /** Запрос списка с фильтрами и сортировкой — для таблицы и для «выбрать всё на странице». */
    protected function listQuery(): Builder
    {
        $term = trim($this->search);
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';
        $stage = $this->active ? CounterpartyStage::Cooperating : CounterpartyStage::tryFrom($this->stage);

        return Counterparty::query()
            // Нужно только первое контактное лицо (как на канбане): limit внутри жадной загрузки — по одному на контрагента.
            ->with(['contacts' => fn ($q) => $q->limit(1)])
            // Проведённые сделки от контрагента — считаются по сделкам (этап «Проведена»), вручную не меняются.
            ->withCount(['deals as done_deals_count' => fn ($q) => $q->where('stage', DealStage::Done)])
            ->when($stage, fn (Builder $q, CounterpartyStage $stage) => $q->stage($stage))
            ->when($term !== '', fn (Builder $q) => $q->search($term))
            // Пустая дата начала сотрудничества — в конце в обоих направлениях (MySQL и SQLite ставят null первым при asc).
            ->when($sort === 'cooperation_started_at', fn (Builder $q) => $q->orderByRaw('cooperation_started_at is null'))
            ->orderBy($sort, $dir)
            ->orderBy('id', $dir);
    }

    public function render()
    {
        $counterparties = $this->listQuery()->paginate(25);

        return view('livewire.counterparty-table', [
            'counterparties' => $counterparties,
            'stages' => CounterpartyStage::cases(),
        ])->title($this->active ? 'Действующие контрагенты' : 'Контрагенты');
    }
}
