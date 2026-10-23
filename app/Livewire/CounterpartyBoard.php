<?php

namespace App\Livewire;

use App\Enums\CounterpartyStage;
use App\Livewire\Concerns\MovesKanbanCards;
use App\Models\Counterparty;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Канбан контрагентов (главный вид раздела): колонки — этапы воронки. Перенос — встроенный в Livewire 4
 * wire:sort (обработчик получает id карточки, новую позицию в колонке и id колонки = значение этапа);
 * логика переноса — трейт MovesKanbanCards (общая с канбаном сделок).
 */
class CounterpartyBoard extends Component
{
    use MovesKanbanCards;

    private const SORTS = ['manual', 'name', 'created'];

    /** Порядок карточек в колонке: manual — как расставили перетаскиванием (position), name, created. */
    #[Url(except: 'manual')]
    public string $sort = 'manual';

    public function updatedSort(): void
    {
        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'manual';
        }
    }

    protected function kanbanModel(): string
    {
        return Counterparty::class;
    }

    protected function kanbanStages(): string
    {
        return CounterpartyStage::class;
    }

    public function render()
    {
        // ponytail: все карточки одним запросом и группировка в PHP — хватает на сотни контрагентов;
        // при тысячах — постраничная подгрузка по колонкам.
        $cards = Counterparty::with('contacts')->get();

        $sorted = match ($this->sort) {
            'name' => $cards->sortBy(fn ($card) => mb_strtolower($card->name), SORT_NATURAL),
            'created' => $cards->sortByDesc('created_at'),
            default => $cards->sortBy([['position', 'asc'], ['id', 'asc']]),
        };

        return view('livewire.counterparty-board', [
            'columns' => collect(CounterpartyStage::cases())->map(fn ($stage) => [
                'stage' => $stage,
                'cards' => $sorted->where('stage', $stage)->values(),
            ]),
            'stages' => CounterpartyStage::cases(),
        ])->title('Контрагенты');
    }
}
