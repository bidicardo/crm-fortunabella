<?php

use App\Enums\CounterpartyStage;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;

it('creates a counterparty with only a name at the first contact stage', function () {
    $counterparty = Counterparty::create(['name' => 'Тестовое агентство'])->fresh();

    expect($counterparty)
        ->name->toBe('Тестовое агентство')
        ->type->toBeNull()
        ->phone->toBeNull()
        ->stage->toBe(CounterpartyStage::FirstContact)
        ->position->toBe(0);
});

it('lists stages in kanban order with labels and badge tones', function () {
    expect(array_map(fn ($s) => [$s->label(), $s->tone()], CounterpartyStage::cases()))->toBe([
        ['Первый контакт', 'stage-inwork'],
        ['Дожим', 'stage-done'],
        ['Сотрудничаем', 'stage-new'],
        ['Отказ', 'stage-refused'],
    ]);
});

it('normalizes phones of the organization and of a contact', function () {
    $counterparty = Counterparty::create(['name' => 'Ресторан', 'phone' => '8 (917) 123-45-67']);
    $contact = $counterparty->contacts()->create(['full_name' => 'Иван Тестов', 'phone' => '+7 900 111-22-33']);

    expect($counterparty->fresh()->phone)->toBe('+79171234567')
        ->and($contact->fresh()->phone)->toBe('+79001112233');
});

it('does not allow mass assignment of stage and position', function () {
    $counterparty = Counterparty::create(['name' => 'Тамада', 'stage' => 'refused', 'position' => 7]);
    $counterparty->update(['stage' => 'cooperating', 'position' => 3]);

    expect($counterparty->fresh())->stage->toBe(CounterpartyStage::FirstContact)->position->toBe(0);
});

it('moves to any stage and logs it without the position', function () {
    $counterparty = Counterparty::create(['name' => 'Площадка']);
    Counterparty::factory()->cooperating()->create()->moveTo(CounterpartyStage::Cooperating, 4);

    $counterparty->moveTo(CounterpartyStage::Cooperating);

    expect($counterparty->fresh())->stage->toBe(CounterpartyStage::Cooperating)->position->toBe(5);

    $log = $counterparty->activityLogs()->where('event', 'updated')->sole();
    expect($log->changes)->toBe(['stage' => ['old' => 'first_contact', 'new' => 'cooperating']])
        ->and($counterparty->activityLabel('stage').': '.$counterparty->activityValue('stage', 'first_contact').' → '.$counterparty->activityValue('stage', 'cooperating'))
        ->toBe('Этап: Первый контакт → Сотрудничаем');

    // Перестановка внутри колонки — только position, в историю не пишется.
    $counterparty->moveTo(CounterpartyStage::Cooperating, 0);
    expect($counterparty->activityLogs()->where('event', 'updated')->count())->toBe(1);
});

it('scopes counterparties by stage', function () {
    $pushing = Counterparty::factory()->pushing()->create();
    Counterparty::factory()->refused()->create();

    expect(Counterparty::stage(CounterpartyStage::Pushing)->pluck('id')->all())->toBe([$pushing->id]);
});

it('has several contacts ordered by name', function () {
    $counterparty = Counterparty::factory()->create();
    CounterpartyContact::factory()->for($counterparty)->create(['full_name' => 'Яков']);
    CounterpartyContact::factory()->for($counterparty)->create(['full_name' => 'Анна']);

    expect($counterparty->contacts->pluck('full_name')->all())->toBe(['Анна', 'Яков'])
        ->and($counterparty->contacts->first()->counterparty->is($counterparty))->toBeTrue();
});

it('logs contact changes in the counterparty history', function () {
    $counterparty = Counterparty::create(['name' => 'Агентство']);

    $contact = $counterparty->contacts()->create(['full_name' => 'Иван Тестов']);
    $contact->update(['position_title' => 'Менеджер', 'full_name' => 'Иван Тестов']);
    $contact->delete();

    $logs = $counterparty->activityLogs()->orderBy('id')->get();

    expect($logs->pluck('event')->all())->toBe(['created', 'contact_added', 'contact_updated', 'contact_removed'])
        ->and($logs[1]->changes)->toBe(['contact' => 'Иван Тестов'])
        ->and($logs[2]->changes)->toBe(['contact' => 'Иван Тестов', 'fields' => ['position_title' => ['old' => null, 'new' => 'Менеджер']]])
        ->and($logs[3]->changes)->toBe(['contact' => 'Иван Тестов']);
});

it('shows the cooperation date in the history as a Russian date', function () {
    expect((new Counterparty)->activityValue('cooperation_started_at', '2026-09-01 00:00:00'))->toBe('01.09.2026');
});

describe('search', function () {
    beforeEach(function () {
        $this->agency = Counterparty::create(['name' => 'Праздник 100%', 'type' => 'Ивент-агентство', 'phone' => '+79171234567']);
        $this->restaurant = Counterparty::create(['name' => 'Ресторан Волна', 'email' => 'volna@example.test']);
        $this->restaurant->contacts()->create(['full_name' => 'Ольга Тестова', 'phone' => '+79001112233', 'email' => 'olga@example.test']);
    });

    $found = fn (string $term) => Counterparty::search($term)->pluck('name')->all();

    it('finds by name, type and email (latin case-insensitively)', function () use ($found) {
        expect($found('Волна'))->toBe(['Ресторан Волна'])
            ->and($found('Ивент'))->toBe(['Праздник 100%'])
            ->and($found('VOLNA@'))->toBe(['Ресторан Волна']);
    });

    it('finds by contact name, phone and email', function () use ($found) {
        expect($found('Ольга'))->toBe(['Ресторан Волна'])
            ->and($found('olga@'))->toBe(['Ресторан Волна'])
            ->and($found('8 900 111'))->toBe(['Ресторан Волна']);
    });

    it('finds by phone written in different ways', function () use ($found) {
        expect($found('8 (917) 123-45-67'))->toBe(['Праздник 100%'])
            ->and($found('+7 917 123'))->toBe(['Праздник 100%'])
            ->and($found('4567'))->toBe(['Праздник 100%']);
    });

    it('treats LIKE wildcards literally', function () use ($found) {
        expect($found('100%'))->toBe(['Праздник 100%'])
            ->and($found('%'))->toBe(['Праздник 100%'])
            ->and($found('_'))->toBe([]);
    });
});

it('deletes contacts together with the counterparty', function () {
    $counterparty = Counterparty::factory()->has(CounterpartyContact::factory()->count(2), 'contacts')->create();
    CounterpartyContact::factory()->create();

    $counterparty->delete();

    expect(CounterpartyContact::count())->toBe(1);
});

it('generates stage states and Russian phones in factories', function () {
    foreach (CounterpartyStage::cases() as $stage) {
        expect(Counterparty::factory()->atStage($stage)->create()->fresh()->stage)->toBe($stage);
    }

    expect(Counterparty::factory()->make()->phone)->toMatch('/^\+7\d{10}$/')
        ->and(CounterpartyContact::factory()->make()->phone)->toMatch('/^\+7\d{10}$/');
});
