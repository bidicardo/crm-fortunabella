<?php

use App\Livewire\DealBoard;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Livewire\DealTable;
use App\Models\Client;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('finds other deals on the same day, ignoring refused deals and the deal itself', function () {
    $deal = Deal::factory()->create(['event_date' => '2026-12-20', 'event_time' => '19:00']);
    $other = Deal::factory()->create(['title' => 'Другая', 'event_date' => '2026-12-20', 'event_time' => '12:00']);
    Deal::factory()->refused()->create(['event_date' => '2026-12-20']);
    Deal::factory()->create(['event_date' => '2026-12-21']);
    $alone = Deal::factory()->create(['event_date' => '2026-12-22']);
    $undated = Deal::factory()->create(['event_date' => null]);
    $refused = Deal::factory()->refused()->create(['event_date' => '2026-12-20']);

    expect(Deal::othersOn('2026-12-20', $deal->id)->pluck('id')->all())->toBe([$other->id])
        ->and(Deal::othersOn('2026-12-22', $alone->id))->toBeEmpty()
        ->and(Deal::othersOn(null))->toBeEmpty();

    $map = Deal::sameDayOthers(Deal::all());
    expect($map->keys()->sort()->values()->all())->toBe(collect([$deal->id, $other->id])->sort()->values()->all())
        ->and($map[$deal->id]->pluck('id')->all())->toBe([$other->id])
        ->and($map->has($alone->id))->toBeFalse()
        ->and($map->has($undated->id))->toBeFalse()
        // У сделки в «Отказе» предупреждения нет — мероприятия не будет.
        ->and($map->has($refused->id))->toBeFalse();

    // Канбан: тот же результат по уже загруженным сделкам, без запросов.
    $all = Deal::with('client:id,name')->get();
    DB::flushQueryLog();
    DB::enableQueryLog();
    expect(Deal::sameDayOthers($all, $all)->keys()->sort()->values()->all())->toBe($map->keys()->sort()->values()->all())
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('shows the warning on the card with the other deals and does not block saving', function () {
    $client = Client::factory()->create(['name' => 'Анна Тестова']);
    $deal = Deal::factory()->create(['event_date' => '2026-12-20']);
    $other = Deal::factory()->for($client)->create(['title' => 'Свадьба', 'event_date' => '2026-12-20', 'event_time' => '18:00']);

    Livewire::test(DealShow::class, ['deal' => $deal])
        ->assertSeeHtml('aria-label="Дата занята: 1 сделка в этот день"')
        ->assertSee('Свадьба')->assertSee('Анна Тестова · 18:00')
        ->assertSeeHtml('href="'.route('deals.show', $other).'"')
        // Сохранение не блокируется: можно оставить ту же дату.
        ->call('edit', 'event_date')->call('save')->assertHasNoErrors();

    Livewire::test(DealShow::class, ['deal' => $deal->fresh()])
        ->call('edit', 'event_date')->set('value', '2026-12-25')->call('save');
    Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->assertDontSeeHtml('Дата занята');
});

it('warns in the creation form as soon as a busy date is chosen and still saves', function () {
    Deal::factory()->create(['title' => 'Уже есть', 'event_date' => '2026-12-20']);

    Livewire::test(DealForm::class)
        ->assertDontSeeHtml('Дата занята')
        ->set('event_date', '2026-12-20')->assertSeeHtml('Дата занята')->assertSee('Уже есть')
        ->set('title', 'Вторая')->call('pickClient', Client::factory()->create()->id)
        ->call('save')->assertHasNoErrors();

    expect(Deal::count())->toBe(2);
});

it('marks busy dates on the kanban and in the list without extra queries per deal', function () {
    Deal::factory()->create(['title' => 'А', 'event_date' => '2026-12-20']);
    Deal::factory()->create(['title' => 'Б', 'event_date' => '2026-12-20']);
    Deal::factory()->create(['title' => 'В', 'event_date' => '2026-12-21']);

    expect(substr_count(Livewire::test(DealBoard::class)->html(), 'aria-label="Дата занята'))->toBe(2)
        ->and(substr_count(Livewire::test(DealTable::class)->html(), 'aria-label="Дата занята'))->toBe(2);

    $count = function (string $component) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test($component);

        return count(DB::getQueryLog());
    };
    $board = $count(DealBoard::class);
    $list = $count(DealTable::class);
    Deal::factory()->count(10)->create(['event_date' => '2026-12-20']);

    expect($count(DealBoard::class))->toBe($board)->and($count(DealTable::class))->toBe($list);
});

it('escapes deal titles in the warning', function () {
    $xss = '<script>alert(1)</script>';
    $deal = Deal::factory()->create(['event_date' => '2026-12-20']);
    Deal::factory()->create(['title' => $xss, 'event_date' => '2026-12-20']);

    Livewire::test(DealShow::class, ['deal' => $deal])->assertDontSeeHtml($xss)->assertSeeHtml(e($xss));
});
