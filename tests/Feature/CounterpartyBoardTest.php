<?php

use App\Enums\CounterpartyStage;
use App\Livewire\CounterpartyBoard;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

/** Названия карточек колонки в порядке показа. */
function column(mixed $component, CounterpartyStage $stage): array
{
    return $component->viewData('columns')->firstWhere('stage', $stage)['cards']->pluck('name')->all();
}

it('shows four columns in stage order with counters and the main fields', function () {
    $agency = Counterparty::factory()->pushing()->create(['name' => 'Агентство', 'type' => 'Ивент-агентство', 'phone' => '+79171234567']);
    CounterpartyContact::factory()->for($agency)->create(['full_name' => 'Ольга Тестова']);
    Counterparty::factory()->pushing()->create(['name' => 'Тамада']);
    Counterparty::factory()->refused()->create(['name' => 'Ресторан']);

    $component = Livewire::test(CounterpartyBoard::class);

    expect($component->viewData('columns')->map(fn ($c) => [$c['stage'], $c['cards']->count()])->all())->toBe([
        [CounterpartyStage::FirstContact, 0],
        [CounterpartyStage::Pushing, 2],
        [CounterpartyStage::Cooperating, 0],
        [CounterpartyStage::Refused, 1],
    ]);

    $component->assertSeeInOrder(['Первый контакт', 'Дожим', 'Сотрудничаем', 'Отказ'])
        ->assertSee('Ивент-агентство')
        ->assertSee('+79171234567')
        ->assertSee('Ольга Тестова')
        ->assertSeeHtml('href="'.route('counterparties.show', $agency).'"')
        ->assertSeeHtml('href="'.route('counterparties.create', ['stage' => 'pushing']).'"')
        ->assertSeeHtml('wire:sort:group-id="pushing"')
        ->assertSeeHtml('wire:sort:item="'.$agency->id.'"')
        // Цвет этапа: линия слева у карточки и окрашенный список этапа (Дожим — stage-booked, Отказ — stage-refused)
        ->assertSeeHtml('border-left: 4px solid var(--color-stage-booked)')
        ->assertSeeHtml('color: var(--color-stage-booked); border-color: var(--color-stage-booked)')
        ->assertSeeHtml('border-left: 4px solid var(--color-stage-refused)');

    $this->get(route('counterparties.index'))->assertOk()->assertSee('Новый контрагент');
});

it('recolors the card after a stage change', function () {
    $card = Counterparty::factory()->pushing()->create();

    Livewire::test(CounterpartyBoard::class)
        ->call('changeStage', $card->id, CounterpartyStage::Cooperating->value)
        ->assertSeeHtml('border-left: 4px solid var(--color-stage-new)')
        ->assertDontSeeHtml('border-left: 4px solid var(--color-stage-booked)');
});

it('puts a new counterparty at the end of its column', function () {
    $first = Counterparty::factory()->create(['name' => 'Первый']);
    $second = Counterparty::factory()->create(['name' => 'Второй']);

    expect([$first->fresh()->position, $second->fresh()->position])->toBe([0, 1]);
});

it('moves a card to another stage at a position, logs it and renumbers the column', function () {
    $a = Counterparty::factory()->cooperating()->create(['name' => 'А']);
    $b = Counterparty::factory()->cooperating()->create(['name' => 'Б']);
    $moved = Counterparty::factory()->create(['name' => 'Новый партнёр']);

    $component = Livewire::test(CounterpartyBoard::class)->call('moveCard', $moved->id, 1, 'cooperating');

    expect(column($component, CounterpartyStage::Cooperating))->toBe(['А', 'Новый партнёр', 'Б'])
        ->and(Counterparty::orderBy('position')->pluck('position', 'name')->all())->toBe(['А' => 0, 'Новый партнёр' => 1, 'Б' => 2])
        ->and($moved->activityLogs()->where('event', 'updated')->sole()->changes)
        ->toBe(['stage' => ['old' => 'first_contact', 'new' => 'cooperating']]);
});

it('reorders cards inside a column without writing history', function () {
    Counterparty::factory()->create(['name' => 'А']);
    Counterparty::factory()->create(['name' => 'Б']);
    $c = Counterparty::factory()->create(['name' => 'В']);

    $component = Livewire::test(CounterpartyBoard::class)->call('moveCard', $c->id, 0, 'first_contact');

    expect(column($component, CounterpartyStage::FirstContact))->toBe(['В', 'А', 'Б'])
        ->and($c->activityLogs()->where('event', 'updated')->count())->toBe(0);
});

it('rejects a wrong stage or a missing card without changes', function () {
    $card = Counterparty::factory()->create();

    Livewire::test(CounterpartyBoard::class)->call('moveCard', $card->id, 0, 'nonsense')->assertNotFound();
    Livewire::test(CounterpartyBoard::class)->call('changeStage', $card->id, 'nonsense')->assertNotFound();
    expect(fn () => Livewire::test(CounterpartyBoard::class)->call('moveCard', 999, 0, 'refused'))
        ->toThrow(ModelNotFoundException::class);

    expect($card->fresh()->stage)->toBe(CounterpartyStage::FirstContact);
});

it('changes the stage without dragging and puts the card first in the column', function () {
    $card = Counterparty::factory()->create(['name' => 'Площадка']);
    Counterparty::factory()->pushing()->create(['name' => 'Тамада']);
    Counterparty::factory()->pushing()->create(['name' => 'Ресторан']);

    $component = Livewire::test(CounterpartyBoard::class)->call('changeStage', $card->id, 'pushing');

    expect(column($component, CounterpartyStage::Pushing))->toBe(['Площадка', 'Тамада', 'Ресторан'])
        ->and($card->activityLogs()->where('event', 'updated')->count())->toBe(1);
});

it('sorts cards by name or creation date; then dragging inside a column keeps the order', function () {
    $this->travelTo(now()->subDays(2));
    $b = Counterparty::factory()->create(['name' => 'Б']);
    $this->travelBack();
    Counterparty::factory()->create(['name' => 'а']);
    Counterparty::factory()->create(['name' => 'В']);

    $component = Livewire::test(CounterpartyBoard::class);
    expect(column($component, CounterpartyStage::FirstContact))->toBe(['Б', 'а', 'В']);

    $component->set('sort', 'name');
    expect(column($component, CounterpartyStage::FirstContact))->toBe(['а', 'Б', 'В']);

    $component->call('moveCard', $b->id, 2, 'first_contact');
    expect(Counterparty::orderBy('position')->pluck('name')->all())->toBe(['Б', 'а', 'В']);

    $component->set('sort', 'created');
    expect(column($component, CounterpartyStage::FirstContact)[2])->toBe('Б');

    // Между колонками перенос работает и в этом режиме.
    $component->call('moveCard', $b->id, 0, 'refused');
    expect($b->fresh()->stage)->toBe(CounterpartyStage::Refused);

    $component->set('sort', 'nonsense')->assertSet('sort', 'manual');
});

it('does not grow the number of queries with the number of cards', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(CounterpartyBoard::class);

        return count(DB::getQueryLog());
    };

    Counterparty::factory()->has(CounterpartyContact::factory(), 'contacts')->create();
    $few = $count();
    Counterparty::factory()->count(15)->has(CounterpartyContact::factory()->count(2), 'contacts')->create();

    expect($count())->toBe($few);
});

it('sends guests to the login page and highlights the menu item', function () {
    $this->get(route('counterparties.index'))->assertOk()->assertSeeHtml('aria-current="page"');

    auth()->logout();
    $this->get(route('counterparties.index'))->assertRedirect(route('login'));
});
