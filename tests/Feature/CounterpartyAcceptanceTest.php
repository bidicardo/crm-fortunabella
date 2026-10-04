<?php

// Задача 25: итоговые проверки Фазы 3 «Контрагенты» — сквозной сценарий и пробелы по критериям приёмки
// (docs/20-acceptance-criteria.md, раздел «Контрагенты»). Остальные критерии закрыты тестами своих задач.

use App\Enums\CounterpartyStage;
use App\Livewire\CounterpartyBoard;
use App\Livewire\CounterpartyContactCard;
use App\Livewire\CounterpartyForm;
use App\Livewire\CounterpartyShow;
use App\Livewire\CounterpartyTable;
use App\Models\Counterparty;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('runs the whole counterparty flow end to end', function () {
    $this->actingAs(User::factory()->create());

    // Создание с телефоном в «восьмёрочном» написании — в БД нормализованный номер, этап «Первый контакт».
    Livewire::test(CounterpartyForm::class)
        ->set('name', 'Агентство Праздник')->set('type', 'Ивент-агентство')->set('phone', '8 917 123-45-67')
        ->call('save')
        ->assertRedirect(route('counterparties.show', Counterparty::sole()));
    $counterparty = Counterparty::sole();
    expect($counterparty)->phone->toBe('+79171234567')->stage->toBe(CounterpartyStage::FirstContact);

    // Два контактных лица в карточке и правка одного поля прямо в карточке.
    Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
        ->call('addContact')->set('contact.full_name', 'Ольга Тестова')->set('contact.phone', '8 900 111-22-33')->call('saveContact')
        ->call('addContact')->set('contact.full_name', 'Борис Тестов')->call('saveContact')
        ->call('edit', 'email')->set('value', 'agency@example.test')->call('save')
        ->assertHasNoErrors();
    expect($counterparty->contacts()->pluck('full_name')->all())->toBe(['Борис Тестов', 'Ольга Тестова'])
        ->and($counterparty->fresh()->email)->toBe('agency@example.test');

    // Перенос на канбане в «Сотрудничаем» — сразу в «Действующих» и в базе с фильтром этапа.
    Livewire::test(CounterpartyBoard::class)->call('moveCard', $counterparty->id, 0, 'cooperating');
    expect($counterparty->fresh()->stage)->toBe(CounterpartyStage::Cooperating);

    Livewire::test(CounterpartyTable::class, ['active' => true])->assertSee('Агентство Праздник');
    Livewire::test(CounterpartyTable::class)->set('stage', 'cooperating')->assertSee('Агентство Праздник')
        ->set('stage', 'first_contact')->assertDontSee('Агентство Праздник');

    // История (развёрнутая): создание, оба контакта, правка и смена этапа.
    $card = Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty->fresh()])
        ->call('showMoreHistory')
        ->assertSee('Контрагент создан')
        ->assertSee('Добавлено контактное лицо: Ольга Тестова')
        ->assertSee('Добавлено контактное лицо: Борис Тестов')
        ->assertSee('agency@example.test');
    expect(preg_replace('/\s+/u', ' ', strip_tags($card->html())))->toContain('Этап: Первый контакт → Сотрудничаем');
});

describe('security and integrity', function () {
    it('keeps every counterparty page away from guests and has no edit page', function () {
        $counterparty = Counterparty::factory()->create();

        foreach (['/counterparties', '/counterparties/list', '/counterparties/active', '/counterparties/create', "/counterparties/{$counterparty->id}"] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        // Действия Livewire (перенос, правка, контакты, удаление) повторяют проверку входа страницы.
        expect(Livewire::getPersistentMiddleware())->toContain(Authenticate::class);

        $this->actingAs(User::factory()->create())->get("/counterparties/{$counterparty->id}/edit")->assertNotFound();
    });

    it('does not let the card or a contact fake the edited field', function () {
        $this->actingAs(User::factory()->create());
        $counterparty = Counterparty::factory()->create();
        $contact = $counterparty->contacts()->create(['full_name' => 'Ольга']);

        expect(fn () => Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])->set('editing', 'stage'))
            ->toThrow(CannotUpdateLockedPropertyException::class)
            ->and(fn () => Livewire::test(CounterpartyContactCard::class, ['contact' => $contact])->set('editing', 'counterparty_id'))
            ->toThrow(CannotUpdateLockedPropertyException::class);

        foreach (['stage', 'position', 'id', 'created_at'] as $field) {
            Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])->call('edit', $field)->assertNotFound();
        }
    });

    it('escapes counterparty fields on the kanban, in both tables and in the history', function () {
        $this->actingAs(User::factory()->create());
        $xss = '<script>alert(1)</script>';
        $counterparty = Counterparty::factory()->cooperating()->create(['name' => $xss, 'type' => $xss]);
        $counterparty->contacts()->create(['full_name' => $xss]);
        $counterparty->update(['email' => 'x@example.test', 'address' => $xss]);

        foreach (['/counterparties', '/counterparties/list', '/counterparties/active', route('counterparties.show', $counterparty)] as $url) {
            $this->get($url)->assertOk()->assertDontSee($xss, false)->assertSee(e($xss), false);
        }

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->call('showMoreHistory')
            ->assertDontSeeHtml($xss)
            ->assertSeeHtml('Добавлено контактное лицо: '.e($xss));
    });
});
