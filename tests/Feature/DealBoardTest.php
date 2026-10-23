<?php

use App\Enums\DealStage;
use App\Livewire\CounterpartyBoard;
use App\Livewire\DealBoard;
use App\Models\Deal;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

/** Названия карточек колонки в порядке показа. */
function dealColumn(mixed $component, DealStage $stage): array
{
    return $component->viewData('columns')->firstWhere('stage', $stage)['cards']->pluck('title')->all();
}

it('shows five columns in stage order with counts, sums and the main card fields', function () {
    $deal = Deal::factory()->booked()->create(['title' => 'Свадьба', 'amount' => 150000, 'event_date' => '2026-12-20', 'event_time' => '19:00', 'lead_source' => 'avito', 'sold_by_id' => User::factory()->create(['name' => 'Пётр Продавец'])->id]);
    Deal::factory()->booked()->create(['amount' => 50000]);
    Deal::factory()->refused()->create(['amount' => null]);

    $component = Livewire::test(DealBoard::class);

    expect($component->viewData('columns')->map(fn ($c) => [$c['stage'], $c['cards']->count(), $c['sum']])->all())->toBe([
        [DealStage::New, 0, 0],
        [DealStage::InWork, 0, 0],
        [DealStage::Booked, 2, 200000],
        [DealStage::Done, 0, 0],
        [DealStage::Refused, 1, 0],
    ]);

    $component->assertSeeInOrder(['Новая заявка', 'В работе', 'Бронь', 'Проведена', 'Отказ'])
        ->assertSee('200 000 ₽')->assertSee('Свадьба')->assertSee($deal->client->name)
        ->assertSee('20.12.2026 19:00')->assertSee('Авито')->assertSee('Предоплата не получена')
        ->assertSeeHtml('href="'.route('deals.show', $deal).'"')
        ->assertSeeHtml('href="'.route('deals.create', ['stage' => 'booked']).'"')
        ->assertSeeHtml('wire:sort:group-id="booked"')
        ->assertSeeHtml('wire:sort:item="'.$deal->id.'"')
        ->assertSeeHtml('[.hide-seller_&]:hidden')->assertSee('Продал: Пётр Продавец')
        // Поля скрываются классом, а не x-show: иначе у копии карточки при переносе (она в body, вне x-data) поля пропадают.
        ->assertDontSeeHtml('x-show="fields.');

    Livewire::test(CounterpartyBoard::class)->assertDontSeeHtml('x-show="fields.');
});

it('counts every analytics tile with its date boundaries and without refused deals', function () {
    $this->travelTo('2026-10-15 12:00');

    // В работе: новая, в работе и бронь; проведённые и отказ не входят.
    Deal::factory()->create(['amount' => 1000]);
    Deal::factory()->inWork()->create(['amount' => 2000]);
    Deal::factory()->refused()->create(['amount' => 99999]);

    // Предстоящие брони: с сегодняшнего дня; вчерашняя бронь — в «В работе», но не в предстоящих.
    Deal::factory()->booked()->withPrepayment(true)->create(['amount' => 10000, 'event_date' => '2026-10-15']);
    Deal::factory()->booked()->withPrepayment(true)->create(['amount' => 20000, 'event_date' => '2026-11-01']);
    Deal::factory()->booked()->withPrepayment(true)->create(['amount' => 40000, 'event_date' => '2026-10-14']);

    // Проведено за месяц: 1-е и последнее число входят, соседние месяцы — нет.
    Deal::factory()->done()->create(['amount' => 100, 'event_date' => '2026-10-01']);
    Deal::factory()->done()->create(['amount' => 200, 'event_date' => '2026-10-31']);
    Deal::factory()->done()->create(['amount' => 400, 'event_date' => '2026-09-30']);
    Deal::factory()->done()->create(['amount' => 800, 'event_date' => '2026-11-01']);

    // Предоплата не получена: только брони без оплаты; сумма — недополученных предоплат.
    Deal::factory()->booked()->create(['amount' => 5000, 'event_date' => null, 'prepayment_amount' => 1500, 'prepayment_paid' => false]);
    Deal::factory()->inWork()->create(['amount' => 0, 'prepayment_amount' => 7000, 'prepayment_paid' => false]);

    $a = DealBoard::analytics();

    expect($a['open'])->toBe(['count' => 7, 'sum' => 1000 + 2000 + 10000 + 20000 + 40000 + 5000 + 0])
        ->and($a['upcoming']['count'])->toBe(2)->and($a['upcoming']['sum'])->toBe(30000)
        ->and($a['upcoming']['next']->format('Y-m-d'))->toBe('2026-10-15')
        ->and($a['done'])->toBe(['count' => 2, 'sum' => 300])
        ->and($a['unpaid'])->toBe(['count' => 1, 'sum' => 1500]);

    Livewire::test(DealBoard::class)
        ->assertSee('В работе')->assertSee('78 000 ₽')->assertSee('7 сделок')
        ->assertSee('2 сделки · ближайшая 15.10')
        ->assertSee('Проведено за месяц')->assertSee('300 ₽')
        ->assertSee('1 сделка')->assertSee('на 1 500 ₽');
});

it('shortens money for narrow places', function () {
    expect(array_map(fn ($v) => Deal::moneyShort($v), [0, 950, 1500, 100000, 999949, 999999, 1250000]))
        ->toBe(['0 ₽', '950 ₽', '1,5 тыс ₽', '100 тыс ₽', '999,9 тыс ₽', '1 млн ₽', '1,25 млн ₽']);
});

it('declines «сделка» by Russian rules', function () {
    expect(array_map(fn ($n) => Deal::countLabel($n), [0, 1, 2, 4, 5, 11, 12, 14, 21, 22, 25, 101, 111, 112]))->toBe([
        '0 сделок', '1 сделка', '2 сделки', '4 сделки', '5 сделок', '11 сделок', '12 сделок', '14 сделок',
        '21 сделка', '22 сделки', '25 сделок', '101 сделка', '111 сделок', '112 сделок',
    ]);
});

it('shows empty analytics without errors', function () {
    expect(DealBoard::analytics())->toBe([
        'open' => ['count' => 0, 'sum' => 0],
        'upcoming' => ['count' => 0, 'sum' => 0, 'next' => null],
        'done' => ['count' => 0, 'sum' => 0],
        'unpaid' => ['count' => 0, 'sum' => 0],
    ]);

    Livewire::test(DealBoard::class)->assertSee('0 сделок')->assertSee('всё получено');
});

it('moves a card to another stage at a position and renumbers the column; analytics follow', function () {
    $a = Deal::factory()->booked()->create(['title' => 'А', 'amount' => 1000, 'event_date' => now()->addDay()->toDateString()]);
    $b = Deal::factory()->booked()->create(['title' => 'Б', 'amount' => null, 'event_date' => null]);
    $c = Deal::factory()->create(['title' => 'В', 'amount' => 5000, 'event_date' => now()->addDay()->toDateString()]);

    $component = Livewire::test(DealBoard::class)->call('moveCard', $c->id, 1, 'booked');

    expect(dealColumn($component, DealStage::Booked))->toBe(['А', 'В', 'Б'])
        ->and($c->activityLogs()->where('event', 'updated')->count())->toBe(1)
        ->and(DealBoard::analytics()['upcoming']['sum'])->toBe(6000);
});

it('changes the stage without dragging and puts the card first; positions never go below zero', function () {
    $deal = Deal::factory()->create(['title' => 'Новая']);
    Deal::factory()->done()->create(['title' => 'Первая']);

    $component = Livewire::test(DealBoard::class)->call('changeStage', $deal->id, 'done');

    expect(dealColumn($component, DealStage::Done))->toBe(['Новая', 'Первая'])
        ->and(Deal::min('position'))->toBeGreaterThanOrEqual(0);
});

it('rejects a wrong stage or a missing card without changes', function () {
    $deal = Deal::factory()->create();

    Livewire::test(DealBoard::class)->call('moveCard', $deal->id, 0, 'nonsense')->assertNotFound();
    Livewire::test(DealBoard::class)->call('changeStage', $deal->id, 'nonsense')->assertNotFound();
    expect(fn () => Livewire::test(DealBoard::class)->call('moveCard', 999, 0, 'done'))->toThrow(ModelNotFoundException::class);

    expect($deal->fresh()->stage)->toBe(DealStage::New);
});

it('sorts cards by event date and amount (empty last) or by creation date', function () {
    Deal::factory()->create(['title' => 'Без даты', 'event_date' => null, 'amount' => null]);
    Deal::factory()->create(['title' => 'Поздняя', 'event_date' => '2026-12-01', 'amount' => 1000]);
    Deal::factory()->create(['title' => 'Ранняя', 'event_date' => '2026-11-01', 'amount' => 9000]);

    expect(dealColumn(Livewire::test(DealBoard::class)->set('sort', 'event'), DealStage::New))->toBe(['Ранняя', 'Поздняя', 'Без даты'])
        ->and(dealColumn(Livewire::test(DealBoard::class)->set('sort', 'amount'), DealStage::New))->toBe(['Ранняя', 'Поздняя', 'Без даты'])
        ->and(dealColumn(Livewire::test(DealBoard::class)->set('sort', 'nonsense'), DealStage::New))->toBe(['Без даты', 'Поздняя', 'Ранняя']);
});

it('does not run more queries for more deals', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(DealBoard::class);

        return count(DB::getQueryLog());
    };

    Deal::factory()->count(3)->create(['sold_by_id' => User::factory()]);
    $few = $count();
    Deal::factory()->count(12)->create(['sold_by_id' => User::factory()]);

    expect($count())->toBe($few);
});

it('serves the page, shows the menu item and leads the back arrow of the card and the form to the kanban', function () {
    $deal = Deal::factory()->create();

    $this->get('/deals')->assertOk()->assertSeeLivewire(DealBoard::class)->assertSee('<title>Сделки', false);

    foreach ([route('deals.show', $deal), route('deals.create')] as $url) {
        $this->get($url);
        expect(Navigation::backUrl())->toBe(route('deals.index'));
    }

    auth()->logout();
    $this->get('/deals')->assertRedirect('/login');
});
