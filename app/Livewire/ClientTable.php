<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Livewire\Concerns\DeletesSelected;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Клиенты')]
class ClientTable extends Component
{
    use DeletesSelected, WithPagination;

    private const SORTABLE = ['name', 'created_at', 'updated_at'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $legalType = '';

    #[Url(as: 'archived')]
    public bool $onlyArchived = false;

    /** Фильтр по сделкам: '' — все, with — со сделками, without — без сделок. */
    #[Url(as: 'deals')]
    public string $deals = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $dir = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLegalType(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyArchived(): void
    {
        $this->resetPage();
    }

    public function updatedDeals(): void
    {
        $this->resetPage();
    }

    // Массово удаляются только действующие клиенты (архивный дубль удаляется вместе со своим основным).
    protected function deletableQuery(): Builder
    {
        return Client::active();
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

        return Client::query()
            // Число сделок — для метки у имени (одним подзапросом, без N+1).
            ->withCount('deals')
            ->when($this->deals === 'with', fn (Builder $q) => $q->has('deals'))
            ->when($this->deals === 'without', fn (Builder $q) => $q->doesntHave('deals'))
            ->when($this->onlyArchived, fn (Builder $q) => $q->whereNotNull('archived_at'), fn (Builder $q) => $q->active())
            ->when(ClientLegalType::tryFrom($this->legalType), fn (Builder $q, ClientLegalType $type) => $q->where('legal_type', $type))
            ->when($term !== '', fn (Builder $q) => $q->search($term))
            ->orderBy($sort, $dir)
            ->orderBy('id', $dir);
    }

    public function render()
    {
        $clients = $this->listQuery()->paginate(25);

        return view('livewire.client-table', [
            'clients' => $clients,
            'legalTypes' => ClientLegalType::cases(),
        ]);
    }
}
