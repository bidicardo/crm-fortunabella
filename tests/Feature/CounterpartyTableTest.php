<?php

use App\Livewire\CounterpartyBoard;
use App\Livewire\CounterpartyTable;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

/** Названия строк таблицы в порядке показа. */
function tableNames(mixed $component): array
{
    return $component->viewData('counterparties')->pluck('name')->all();
}

/** Адрес стрелки «Назад» в верхней панели страницы. */
function backLink(string $html): ?string
{
    return preg_match('/href="([^"]+)"\s+aria-label="Назад"/', $html, $m) ? $m[1] : null;
}

it('searches by name, type and contact person', function () {
    $agency = Counterparty::factory()->create(['name' => 'Агентство Праздник', 'type' => 'Ивент-агентство']);
    CounterpartyContact::factory()->for($agency)->create(['full_name' => 'Ольга Тестова']);
    Counterparty::factory()->create(['name' => 'Ресторан Уют', 'type' => 'Ресторан']);

    // Регистр — как в данных: SQLite в тестах не сравнивает кириллицу без учёта регистра (MySQL — сравнивает).
    foreach (['Праздник', 'Ивент', 'Ольга'] as $term) {
        Livewire::test(CounterpartyTable::class)->set('search', $term)->assertSee('Агентство Праздник')->assertDontSee('Ресторан Уют');
    }
});

it('searches by organization and contact phone in different notations', function (string $term) {
    Counterparty::factory()->create(['name' => 'По телефону', 'phone' => '8 917 123-45-67']);
    $other = Counterparty::factory()->create(['name' => 'По контакту', 'phone' => '+79260001122']);
    CounterpartyContact::factory()->for($other)->create(['phone' => '+79031112233']);

    Livewire::test(CounterpartyTable::class)->set('search', $term)->assertSee('По телефону')->assertDontSee('По контакту');
    Livewire::test(CounterpartyTable::class)->set('search', '8 903 111')->assertSee('По контакту')->assertDontSee('По телефону');
})->with(['8 917', '+7917', '917 123', '89171234567', '+7 (917) 123-45-67']);

it('treats LIKE wildcards literally', function () {
    Counterparty::factory()->create(['name' => 'Скидка 100%']);
    Counterparty::factory()->create(['name' => 'Обычный', 'social' => 'a_b']);
    Counterparty::factory()->create(['name' => 'Другой', 'social' => 'axb']);

    Livewire::test(CounterpartyTable::class)->set('search', '%')->assertSee('Скидка 100%')->assertDontSee('Обычный');
    Livewire::test(CounterpartyTable::class)->set('search', 'a_b')->assertSee('Обычный')->assertDontSee('Другой');
    Livewire::test(CounterpartyTable::class)->set('search', '!')->assertSee('Контрагентов не найдено');
});

it('filters by stage and ignores an unknown stage', function () {
    Counterparty::factory()->pushing()->create(['name' => 'В дожиме']);
    Counterparty::factory()->refused()->create(['name' => 'Отказавший']);

    Livewire::test(CounterpartyTable::class)->set('stage', 'pushing')->assertSee('В дожиме')->assertDontSee('Отказавший');
    Livewire::test(CounterpartyTable::class)->set('stage', 'nonsense')->assertSee('В дожиме')->assertSee('Отказавший');
});

it('sorts by name, cooperation start and creation date; ignores unknown columns', function () {
    Counterparty::factory()->create(['name' => 'Борис', 'cooperation_started_at' => '2026-01-10', 'created_at' => now()->subDay()]);
    Counterparty::factory()->create(['name' => 'Анна', 'cooperation_started_at' => '2026-03-01', 'created_at' => now()]);

    $component = Livewire::test(CounterpartyTable::class);
    expect(tableNames($component))->toBe(['Анна', 'Борис']);

    $component->call('sortBy', 'phone')->assertSet('sort', 'created_at');
    $component->call('sortBy', 'name')->assertSet('dir', 'asc');
    expect(tableNames($component))->toBe(['Анна', 'Борис']);

    $component->call('sortBy', 'cooperation_started_at')->assertSet('dir', 'asc');
    expect(tableNames($component))->toBe(['Борис', 'Анна']);

    $component->call('sortBy', 'cooperation_started_at')->assertSet('dir', 'desc');
    expect(tableNames($component))->toBe(['Анна', 'Борис']);
});

it('paginates by 25', function () {
    Counterparty::factory()->count(30)->create();

    $component = Livewire::test(CounterpartyTable::class);
    expect($component->viewData('counterparties')->count())->toBe(25);

    $component->call('gotoPage', 2);
    expect($component->viewData('counterparties')->count())->toBe(5);
});

it('shows the stage badge, the main contact and the stage column only in the base', function () {
    $agency = Counterparty::factory()->cooperating()->create(['name' => 'Агентство']);
    CounterpartyContact::factory()->for($agency)->create(['full_name' => 'Борис Второй']);
    CounterpartyContact::factory()->for($agency)->create(['full_name' => 'Анна Первая']);

    Livewire::test(CounterpartyTable::class)
        ->assertSee('Анна Первая')->assertDontSee('Борис Второй')
        ->assertSee('Все этапы')->assertSeeHtml('x-show="cols.stage"')->assertSee('Сотрудничаем');

    Livewire::test(CounterpartyTable::class, ['active' => true])
        ->assertSee('Анна Первая')
        ->assertDontSee('Все этапы')->assertDontSeeHtml('x-show="cols.stage"')->assertDontSee('Список');
});

it('shows only cooperating counterparties on the active page and the flag cannot be changed', function () {
    Counterparty::factory()->cooperating()->create(['name' => 'Партнёр']);
    Counterparty::factory()->pushing()->create(['name' => 'Кандидат']);

    Livewire::test(CounterpartyTable::class, ['active' => true])
        ->set('stage', 'pushing')
        ->assertSee('Партнёр')->assertDontSee('Кандидат');

    expect(fn () => Livewire::test(CounterpartyTable::class)->set('active', true))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('adds a counterparty to the active page right after it moves to Cooperating on the kanban', function () {
    $dragged = Counterparty::factory()->create(['name' => 'Перетащили']);
    $selected = Counterparty::factory()->create(['name' => 'Выбрали в списке']);

    Livewire::test(CounterpartyTable::class, ['active' => true])->assertSee('Контрагентов не найдено');

    Livewire::test(CounterpartyBoard::class)
        ->call('moveCard', $dragged->id, 0, 'cooperating')
        ->call('changeStage', $selected->id, 'cooperating');

    Livewire::test(CounterpartyTable::class, ['active' => true])->assertSee('Перетащили')->assertSee('Выбрали в списке');
});

it('shows the empty state and the new counterparty link', function () {
    Livewire::test(CounterpartyTable::class)->assertSee('Контрагентов не найдено')->assertSee(route('counterparties.create'), false);
});

it('serves both pages with titles, menu highlight and back arrow', function () {
    $list = $this->get('/counterparties/list')->assertOk()->assertSeeLivewire(CounterpartyTable::class)->assertSee('<title>Контрагенты', false);
    // Список — вид раздела «Контрагенты»: пункт подсвечен, «Назад» — на канбан, переключатель ведёт на канбан.
    expect($list->getContent())->toMatch('/href="'.preg_quote(route('counterparties.index'), '/').'"\s+title="Контрагенты"\s+aria-current="page"/')
        ->and(backLink($list->getContent()))->toBe(route('counterparties.index'));
    $list->assertSee('href="'.route('counterparties.index').'"', false);

    $active = $this->get('/counterparties/active')->assertOk()->assertSee('<title>Действующие контрагенты', false);
    expect($active->getContent())->toMatch('/href="'.preg_quote(route('counterparties.active'), '/').'"\s+title="Действующие контрагенты"\s+aria-current="page"/')
        ->not->toMatch('/href="'.preg_quote(route('counterparties.index'), '/').'"\s+title="Контрагенты"\s+aria-current="page"/')
        ->and(backLink($active->getContent()))->toBe(route('home'));

    // С канбана переключатель ведёт на список.
    $this->get('/counterparties')->assertSee('href="'.route('counterparties.list').'"', false);
});

it('redirects guests to login', function () {
    auth()->logout();

    $this->get('/counterparties/list')->assertRedirect('/login');
    $this->get('/counterparties/active')->assertRedirect('/login');
});

it('does not run more queries for more counterparties', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(CounterpartyTable::class);

        return count(DB::getQueryLog());
    };

    Counterparty::factory()->count(3)->has(CounterpartyContact::factory()->count(2), 'contacts')->create();
    $few = $count();

    Counterparty::factory()->count(10)->has(CounterpartyContact::factory()->count(2), 'contacts')->create();

    expect($count())->toBe($few);
});
