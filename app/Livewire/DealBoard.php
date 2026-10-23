<?php

namespace App\Livewire;

use App\Enums\DealStage;
use App\Livewire\Concerns\MovesKanbanCards;
use App\Models\Deal;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Канбан сделок (главный вид раздела «Сделки», задача 28): мини-аналитика над колонками (референс
 * 11-kanban-deals-design) и 5 колонок по этапам с количеством и суммой. Перенос — wire:sort, логика —
 * трейт MovesKanbanCards (общая с канбаном контрагентов); оформление — своё.
 */
class DealBoard extends Component
{
    use MovesKanbanCards;

    private const SORTS = ['manual', 'event', 'amount', 'created'];

    /** Порядок карточек в колонке: manual — как расставили (position), event — по дате проведения, amount — по сумме, created. */
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
        return Deal::class;
    }

    protected function kanbanStages(): string
    {
        return DealStage::class;
    }

    /**
     * Плитки аналитики одним агрегирующим запросом («Отказ» не входит ни в одну):
     * в работе — Новая заявка + В работе + Бронь; предстоящие брони — Бронь с датой от сегодня (и ближайшая
     * дата); проведено за месяц — Проведена с датой в текущем месяце; предоплата не получена — Бронь без
     * оплаченной предоплаты (сумма — недополученных предоплат).
     */
    public static function analytics(): array
    {
        $today = Carbon::today()->toDateString();
        // Верхняя граница месяца — «меньше 1-го числа следующего»: SQLite хранит дату со временем (… 00:00:00),
        // и between по строке потерял бы последний день.
        [$monthStart, $nextMonth] = [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->startOfMonth()->addMonth()->toDateString()];
        $open = "stage in ('new', 'in_work', 'booked')";
        $upcoming = "stage = 'booked' and event_date >= ?";
        $done = "stage = 'done' and event_date >= ? and event_date < ?";
        $unpaid = "stage = 'booked' and prepayment_paid = 0";

        $row = Deal::query()->selectRaw(
            "count(case when $open then 1 end) as open_count, sum(case when $open then amount end) as open_sum,
             count(case when $upcoming then 1 end) as upcoming_count, sum(case when $upcoming then amount end) as upcoming_sum,
             min(case when $upcoming then event_date end) as upcoming_next,
             count(case when $done then 1 end) as done_count, sum(case when $done then amount end) as done_sum,
             count(case when $unpaid then 1 end) as unpaid_count, sum(case when $unpaid then prepayment_amount end) as unpaid_sum",
            [$today, $today, $today, $monthStart, $nextMonth, $monthStart, $nextMonth]
        )->toBase()->first();

        return [
            'open' => ['count' => (int) $row->open_count, 'sum' => (int) $row->open_sum],
            'upcoming' => ['count' => (int) $row->upcoming_count, 'sum' => (int) $row->upcoming_sum, 'next' => $row->upcoming_next ? Carbon::parse($row->upcoming_next) : null],
            'done' => ['count' => (int) $row->done_count, 'sum' => (int) $row->done_sum],
            'unpaid' => ['count' => (int) $row->unpaid_count, 'sum' => (int) $row->unpaid_sum],
        ];
    }

    public function render()
    {
        // ponytail: все карточки одним запросом и группировка в PHP — хватает на сотни сделок;
        // при тысячах — постраничная подгрузка по колонкам и суммы колонок агрегатом.
        $cards = Deal::with(['client:id,name', 'soldBy:id,name'])->get();

        // Пустые даты и суммы — в конце колонки.
        $sorted = match ($this->sort) {
            'event' => $cards->sortBy([fn ($a, $b) => [$a->event_date === null, $a->event_date, $a->event_time] <=> [$b->event_date === null, $b->event_date, $b->event_time]]),
            'amount' => $cards->sortBy([fn ($a, $b) => [$a->amount === null, -$a->amount] <=> [$b->amount === null, -$b->amount]]),
            'created' => $cards->sortByDesc('created_at'),
            default => $cards->sortBy([['position', 'asc'], ['id', 'asc']]),
        };

        return view('livewire.deal-board', [
            'columns' => collect(DealStage::cases())->map(function ($stage) use ($sorted) {
                $cards = $sorted->where('stage', $stage)->values();

                return ['stage' => $stage, 'cards' => $cards, 'sum' => (int) $cards->sum('amount')];
            }),
            'stages' => DealStage::cases(),
            'analytics' => self::analytics(),
            // Занятые даты — по уже загруженным карточкам, без запросов.
            'sameDay' => Deal::sameDayOthers($cards, $cards),
        ])->title('Сделки');
    }
}
