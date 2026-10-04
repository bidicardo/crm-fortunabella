<?php

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\TableType;
use App\Livewire\ClientMerge;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\Deal;
use App\Models\User;
use App\Services\ClientMergeService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

it('creates a deal with a title and a client at the new stage, at the end of the column', function () {
    $client = Client::factory()->create();
    $first = Deal::create(['title' => 'Свадьба Ивановых', 'client_id' => $client->id]);
    $second = Deal::create(['title' => 'Корпоратив', 'client_id' => $client->id]);

    expect($first->fresh())->stage->toBe(DealStage::New)->position->toBe(0)->prepayment_paid->toBeFalse()
        ->and($second->fresh()->position)->toBe(1)
        ->and($first->activityLogs()->where('event', 'created')->count())->toBe(1);
});

it('requires a title and an active client; other fields are optional', function () {
    $rules = Deal::validationRules();
    $archived = Client::factory()->archived()->create();
    $blocked = User::factory()->create(['blocked_at' => now()]);

    expect(Validator::make([], $rules)->errors()->keys())->toBe(['title', 'client_id'])
        ->and(Validator::make(['title' => 'Т', 'client_id' => Client::factory()->create()->id], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['title' => 'Т', 'client_id' => $archived->id], $rules)->errors()->has('client_id'))->toBeTrue()
        ->and(Validator::make(['title' => 'Т', 'client_id' => $archived->id, 'sold_by_id' => $blocked->id], $rules)->errors()->has('sold_by_id'))->toBeTrue();

    $bad = ['amount' => -1, 'prepayment_amount' => 1.5, 'duration_hours' => 0, 'event_time' => '25:00', 'table_types' => ['poker'], 'lead_source' => 'radio'];
    expect(Validator::make($bad, $rules)->errors()->keys())
        ->toContain('amount', 'prepayment_amount', 'duration_hours', 'event_time', 'table_types.0', 'lead_source')
        // Длительность — не больше 12 часов (решение владельца).
        ->and(Validator::make(['duration_hours' => 13], $rules)->errors()->has('duration_hours'))->toBeTrue()
        ->and(Validator::make(['duration_hours' => 12], $rules)->errors()->has('duration_hours'))->toBeFalse();

    // Без клиента в БД сделка не сохраняется.
    expect(fn () => Deal::create(['title' => 'Без клиента']))->toThrow(QueryException::class);
});

it('does not allow mass assignment of stage and position', function () {
    $deal = Deal::create(['title' => 'Т', 'client_id' => Client::factory()->create()->id, 'stage' => 'done', 'position' => 7]);
    $deal->update(['stage' => 'refused', 'position' => 3]);

    expect($deal->fresh())->stage->toBe(DealStage::New)->position->toBe(0);
});

it('moves to any stage and logs it without the position', function () {
    $deal = Deal::factory()->create();
    Deal::factory()->booked()->create()->moveTo(DealStage::Booked, 4);

    $deal->moveTo(DealStage::Booked);
    expect($deal->fresh())->stage->toBe(DealStage::Booked)->position->toBe(5);

    $log = $deal->activityLogs()->where('event', 'updated')->sole();
    expect($log->changes)->toBe(['stage' => ['old' => 'new', 'new' => 'booked']])
        ->and($deal->activityValue('stage', 'new').' → '.$deal->activityValue('stage', 'booked'))->toBe('Новая заявка → Бронь');

    $deal->moveTo(DealStage::Booked, 0);
    $deal->moveTo(DealStage::Refused);
    $deal->moveTo(DealStage::New);
    expect($deal->activityLogs()->where('event', 'updated')->count())->toBe(3);
});

it('lists stages in kanban order with labels and tones', function () {
    expect(array_map(fn ($s) => [$s->label(), $s->tone()], DealStage::cases()))->toBe([
        ['Новая заявка', 'stage-new'],
        ['В работе', 'stage-inwork'],
        ['Бронь', 'stage-booked'],
        ['Проведена', 'stage-done'],
        ['Отказ', 'stage-refused'],
    ])->and(array_map(fn ($s) => $s->label(), LeadSource::cases()))->toBe(['Авито', 'Сайт', 'Сарафан', 'Контрагент', 'Другое'])
        ->and(array_map(fn ($t) => $t->label(), TableType::cases()))->toBe(['Европейская рулетка', 'Блэк-джек', 'Русский покер', 'Техас']);
});

it('links a client, a counterparty and users both ways', function () {
    $client = Client::factory()->create();
    $counterparty = Counterparty::factory()->create();
    $seller = User::factory()->create();
    $holder = User::factory()->create();
    $deal = Deal::factory()->for($client)->for($counterparty)->create(['sold_by_id' => $seller->id, 'prepayment_holder_id' => $holder->id]);

    expect($deal->client->is($client))->toBeTrue()
        ->and($deal->counterparty->is($counterparty))->toBeTrue()
        ->and($deal->soldBy->is($seller))->toBeTrue()
        ->and($deal->prepaymentHolder->is($holder))->toBeTrue()
        ->and($client->deals->pluck('id')->all())->toBe([$deal->id])
        ->and($counterparty->deals->pluck('id')->all())->toBe([$deal->id]);

    // Пользователь удалён — сделка остаётся без него.
    $seller->delete();
    expect($deal->fresh()->sold_by_id)->toBeNull();
});

it('shows every field in the history with a Russian label and a readable value', function () {
    $client = Client::factory()->create(['name' => 'Анна Тестова']);
    $user = User::factory()->create(['name' => 'Пётр Продавец']);
    $deal = Deal::factory()->for($client)->create();

    expect($deal->activityValue('amount', 1250000))->toBe('1 250 000 ₽')
        ->and($deal->activityValue('table_types', '["blackjack","texas"]'))->toBe('Блэк-джек, Техас')
        ->and($deal->activityValue('table_types', '[]'))->toBeNull()
        ->and($deal->activityValue('duration_hours', 4))->toBe('4 ч')
        ->and($deal->activityValue('event_date', '2026-12-31'))->toBe('31.12.2026')
        ->and($deal->activityValue('event_time', '19:30:00'))->toBe('19:30')
        ->and($deal->activityValue('lead_source', 'word_of_mouth'))->toBe('Сарафан')
        ->and($deal->activityValue('prepayment_paid', true))->toBe('да')
        ->and($deal->activityValue('prepayment_paid', false))->toBe('нет')
        ->and($deal->activityValue('client_id', $client->id))->toBe('Анна Тестова')
        ->and($deal->activityValue('sold_by_id', $user->id))->toBe('Пётр Продавец')
        ->and($deal->activityValue('sold_by_id', 999))->toBe('№ 999')
        ->and($deal->activityLabel('sold_by_id'))->toBe('Кто продал');

    $deal->update(['amount' => 99000]);
    expect($deal->activityLogs()->where('event', 'updated')->sole()->changes)->toHaveKey('amount');
});

it('moves the duplicate deals to the main client on merge', function () {
    $main = Client::factory()->create();
    $duplicate = Client::factory()->create();
    $deals = Deal::factory()->count(2)->for($duplicate)->create();
    Deal::factory()->for($main)->create();

    // Экран слияния показывает настоящие счётчики сделок обеих карточек.
    $this->actingAs(User::factory()->create());
    $html = Livewire::test(ClientMerge::class, ['client' => $main])->call('pick', $duplicate->id)->html();
    expect(preg_replace('/\s+/u', ' ', strip_tags($html)))->toContain('Сделки 1 2');

    app(ClientMergeService::class)->merge($main, $duplicate, [], User::factory()->create());

    expect($main->deals()->count())->toBe(3)
        ->and($duplicate->deals()->count())->toBe(0)
        ->and($deals->first()->fresh()->client_id)->toBe($main->id);
});

it('keeps money, guests and positions non-negative (unsigned in MySQL)', function () {
    $rules = Deal::validationRules();

    foreach (['amount', 'prepayment_amount', 'guests'] as $field) {
        expect(Validator::make([$field => -1], $rules)->errors()->has($field))->toBeTrue();
    }

    $deal = Deal::factory()->create();
    $deal->moveTo(DealStage::Done);
    expect(Deal::min('position'))->toBeGreaterThanOrEqual(0);
});
