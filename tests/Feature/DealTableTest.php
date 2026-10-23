<?php

use App\Livewire\DealTable;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\Deal;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

/** Названия строк таблицы в порядке показа. */
function dealTitles(mixed $component): array
{
    return $component->viewData('deals')->pluck('title')->all();
}

it('searches by title, address, client (name and phone) and counterparty; LIKE wildcards are literal', function () {
    $client = Client::factory()->create(['name' => 'Анна Тестова', 'phone' => '+79171234567']);
    Deal::factory()->for($client)->for(Counterparty::factory()->state(['name' => 'Ресторан Уют']))->create(['title' => 'Свадьба', 'address' => 'Тверская, 1']);
    Deal::factory()->create(['title' => 'Скидка 100%', 'address' => null]);

    foreach (['Свадьба', 'Тверская', 'Анна', '8 917 123', 'Уют'] as $term) {
        expect(dealTitles(Livewire::test(DealTable::class)->set('search', $term)))->toBe(['Свадьба']);
    }

    expect(dealTitles(Livewire::test(DealTable::class)->set('search', '%')))->toBe(['Скидка 100%']);
    Livewire::test(DealTable::class)->set('search', 'нет такого')->assertSee('Сделок не найдено');
});

it('filters by stage and lead source, ignores unknown values', function () {
    Deal::factory()->booked()->create(['title' => 'Бронь', 'lead_source' => 'avito']);
    Deal::factory()->create(['title' => 'Новая', 'lead_source' => 'site']);

    expect(dealTitles(Livewire::test(DealTable::class)->set('stage', 'booked')))->toBe(['Бронь'])
        ->and(dealTitles(Livewire::test(DealTable::class)->set('source', 'site')))->toBe(['Новая'])
        ->and(Livewire::test(DealTable::class)->set('stage', 'nonsense')->viewData('deals')->count())->toBe(2);
});

it('sorts by event date and amount with empty values last, by title; ignores unknown columns', function () {
    Deal::factory()->create(['title' => 'Пустая', 'event_date' => null, 'amount' => null]);
    Deal::factory()->create(['title' => 'Поздняя', 'event_date' => '2026-12-01', 'amount' => 1000]);
    Deal::factory()->create(['title' => 'Ранняя', 'event_date' => '2026-11-01', 'amount' => 9000]);

    $list = Livewire::test(DealTable::class)->call('sortBy', 'event_date');
    expect(dealTitles($list))->toBe(['Ранняя', 'Поздняя', 'Пустая']);
    $list->call('sortBy', 'event_date');
    expect(dealTitles($list))->toBe(['Поздняя', 'Ранняя', 'Пустая']);

    $list->call('sortBy', 'amount')->call('sortBy', 'amount');
    expect(dealTitles($list))->toBe(['Ранняя', 'Поздняя', 'Пустая']);

    $list->call('sortBy', 'title');
    expect(dealTitles($list))->toBe(['Поздняя', 'Пустая', 'Ранняя']);

    $list->call('sortBy', 'client_id')->assertSet('sort', 'title');
});

it('paginates by 25 and shows the main columns', function () {
    $deal = Deal::factory()->booked()->create(['title' => 'Свадьба', 'amount' => 150000, 'prepayment_amount' => 45000, 'prepayment_paid' => false, 'event_date' => '2026-12-20', 'event_time' => '19:00']);
    Deal::factory()->count(29)->create();

    $list = Livewire::test(DealTable::class)->call('sortBy', 'title');
    expect($list->viewData('deals')->count())->toBe(25);
    $list->call('gotoPage', 2);
    expect($list->viewData('deals')->count())->toBe(5);

    Livewire::test(DealTable::class)->set('search', 'Свадьба')
        ->assertSee('150 000 ₽')->assertSee('20.12.2026 19:00')->assertSee('Бронь')->assertSee('45 000 ₽')->assertSee('не получена')
        ->assertSeeHtml('href="'.route('deals.show', $deal).'"')->assertSeeHtml('href="'.route('clients.show', $deal->client_id).'"');
});

it('deletes the selected deals with their history', function () {
    [$a, $b, $kept] = Deal::factory()->count(3)->create();
    $a->update(['amount' => 12345]);

    Livewire::test(DealTable::class)
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->assertSeeHtml('wire:confirm="Удалить выбранные сделки (2)')
        ->call('deleteSelected')
        ->assertSee('Удалено: 2.');

    expect(Deal::pluck('id')->all())->toBe([$kept->id])
        ->and(ActivityLog::where('subject_type', $a->getMorphClass())->whereIn('subject_id', [$a->id, $b->id])->count())->toBe(0);
});

it('switches views both ways, highlights the menu, leads back to the kanban; guests go to login', function () {
    $this->get('/deals/list')->assertOk()->assertSeeLivewire(DealTable::class)
        ->assertSee('<title>Сделки', false)->assertSee('href="'.route('deals.index').'"', false);
    expect(Navigation::backUrl())->toBe(route('deals.index'));

    $this->get('/deals')->assertSee('href="'.route('deals.list').'"', false);

    auth()->logout();
    $this->get('/deals/list')->assertRedirect('/login');
});

it('does not run more queries for more deals', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(DealTable::class);

        return count(DB::getQueryLog());
    };

    Deal::factory()->count(3)->for(Counterparty::factory())->create(['sold_by_id' => User::factory()]);
    $few = $count();
    Deal::factory()->count(12)->for(Counterparty::factory())->create(['sold_by_id' => User::factory()]);

    expect($count())->toBe($few);
});
