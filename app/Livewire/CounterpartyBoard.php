<?php

namespace App\Livewire;

use App\Enums\CounterpartyStage;
use App\Models\Counterparty;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Канбан контрагентов (главный вид раздела): колонки — этапы воронки. Перенос — встроенный в Livewire 4
 * wire:sort (обработчик получает id карточки, новую позицию в колонке и id колонки = значение этапа).
 */
class CounterpartyBoard extends Component
{
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

    /** Перенос перетаскиванием: в другой этап и/или на другое место в колонке. */
    public function moveCard(mixed $id, mixed $position, mixed $stage): void
    {
        $stage = CounterpartyStage::tryFrom((string) $stage) ?? abort(404);
        $card = Counterparty::findOrFail((int) $id);

        // При сортировке не «Вручную» порядок задаёт сортировка: внутри колонки ничего не меняем,
        // в другую колонку — ставим в конец её ручного порядка.
        if ($this->sort !== 'manual') {
            if ($stage !== $card->stage) {
                $card->moveTo($stage);
            }

            return;
        }

        $ids = Counterparty::stage($stage)->whereKeyNot($card->id)->orderBy('position')->orderBy('id')->pluck('id')->all();
        $position = max(0, min((int) $position, count($ids)));
        array_splice($ids, $position, 0, [$card->id]);

        DB::transaction(function () use ($card, $stage, $position, $ids) {
            // Смена этапа пишется в историю; position — служебное поле, в историю не попадает.
            $card->moveTo($stage, $position);

            // Порядок всей колонки — одним запросом: position = номер по порядку.
            $cases = str_repeat('when ? then ? ', count($ids));
            $in = implode(',', array_fill(0, count($ids), '?'));
            $bindings = collect($ids)->flatMap(fn ($id, $i) => [$id, $i])->merge($ids)->all();
            DB::update("update counterparties set position = case id {$cases}end where id in ($in)", $bindings);
        });
    }

    /** Смена этапа без перетаскивания (список на карточке): в конец колонки. */
    public function changeStage(mixed $id, mixed $stage): void
    {
        $stage = CounterpartyStage::tryFrom((string) $stage) ?? abort(404);
        $card = Counterparty::findOrFail((int) $id);

        if ($stage !== $card->stage) {
            $card->moveTo($stage);
        }
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
