<?php

namespace App\Livewire;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Livewire\Concerns\DeletesSelected;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Список сделок (/deals/list, вид «Список» раздела «Сделки», задача 28а) с массовым удалением. */
class DealTable extends Component
{
    use DeletesSelected, WithPagination;

    private const SORTABLE = ['title', 'event_date', 'amount', 'created_at', 'updated_at'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $stage = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $dir = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    public function updatedSource(): void
    {
        $this->resetPage();
    }

    protected function deletableQuery(): Builder
    {
        return Deal::query();
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

    public function render()
    {
        $term = trim($this->search);
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $deals = Deal::query()
            ->with(['client:id,name', 'counterparty:id,name', 'soldBy:id,name'])
            ->when(DealStage::tryFrom($this->stage), fn (Builder $q, DealStage $stage) => $q->stage($stage))
            ->when(LeadSource::tryFrom($this->source), fn (Builder $q, LeadSource $source) => $q->where('lead_source', $source))
            ->when($term !== '', fn (Builder $q) => $q->search($term))
            // Пустые дата и сумма — в конце в обоих направлениях (MySQL и SQLite ставят null первым при asc).
            ->when(in_array($sort, ['event_date', 'amount'], true), fn (Builder $q) => $q->orderByRaw("$sort is null"))
            ->orderBy($sort, $dir)
            ->when($sort === 'event_date', fn (Builder $q) => $q->orderBy('event_time', $dir))
            ->orderBy('id', $dir)
            ->paginate(25);

        return view('livewire.deal-table', [
            'deals' => $deals,
            'stages' => DealStage::cases(),
            'sources' => LeadSource::cases(),
        ])->title('Сделки');
    }
}
