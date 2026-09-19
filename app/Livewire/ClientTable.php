<?php

namespace App\Livewire;

use App\Enums\ClientLegalType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Клиенты')]
class ClientTable extends Component
{
    use WithPagination;

    private const SORTABLE = ['name', 'created_at', 'updated_at'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $legalType = '';

    #[Url(as: 'archived')]
    public bool $showArchived = false;

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

    public function updatedShowArchived(): void
    {
        $this->resetPage();
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

    /** Подстрока без учёта регистра (в MySQL — за счёт collation); %, _ и ! из запроса экранируются. */
    private function applySearch(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $q) use ($like, $term) {
            foreach (['name', 'email', 'social', 'role'] as $column) {
                $q->orWhereRaw("$column like ? escape '!'", [$like]);
            }

            // Запрос из одних цифр и знаков номера ищем и по нормализованному телефону: 8 → 7.
            if (preg_match('/^[\d\s+()\-]+$/', $term)) {
                $digits = preg_replace('/\D/', '', $term);
                $digits = str_starts_with($digits, '8') ? '7'.substr($digits, 1) : $digits;

                if ($digits !== '') {
                    $q->orWhere('phone', 'like', "%$digits%");
                }
            }
        });
    }

    public function render()
    {
        $term = trim($this->search);
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $clients = Client::query()
            ->when(! $this->showArchived, fn (Builder $q) => $q->active())
            ->when(ClientLegalType::tryFrom($this->legalType), fn (Builder $q, ClientLegalType $type) => $q->where('legal_type', $type))
            ->when($term !== '', fn (Builder $q) => $this->applySearch($q, $term))
            ->orderBy($sort, $dir)
            ->orderBy('id', $dir)
            ->paginate(25);

        return view('livewire.client-table', [
            'clients' => $clients,
            'legalTypes' => ClientLegalType::cases(),
        ]);
    }
}
