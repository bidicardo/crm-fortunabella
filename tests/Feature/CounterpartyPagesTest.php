<?php

use App\Enums\CounterpartyStage;
use App\Livewire\CounterpartyContactCard;
use App\Livewire\CounterpartyForm;
use App\Livewire\CounterpartyShow;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

describe('creation', function () {
    it('creates a counterparty with only a name and opens the card', function () {
        Livewire::test(CounterpartyForm::class)
            ->set('name', 'Тестовое агентство')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('counterparties.show', 1));

        $counterparty = Counterparty::firstOrFail();
        expect($counterparty->stage)->toBe(CounterpartyStage::FirstContact)
            ->and($counterparty->phone)->toBeNull()
            ->and($counterparty->activityLogs()->pluck('event')->all())->toBe(['created']);

        $this->get(route('counterparties.show', $counterparty))->assertOk()->assertSee('Тестовое агентство');
    });

    it('creates a counterparty with all fields', function () {
        Livewire::test(CounterpartyForm::class)
            ->set('name', 'Ресторан Волна')
            ->set('type', 'Ресторан')
            ->set('phone', '8 917 123-45-67')
            ->set('email', 'volna@example.test')
            ->set('social', '@volna')
            ->set('telegram', 't.me/volna')
            ->set('website', 'volna.example.test')
            ->set('cooperation_started_at', '2026-09-01')
            ->set('address', 'Москва, ул. Тестовая, 1')
            ->set('cooperation_terms', "10 %\nоплата после мероприятия")
            ->call('save')
            ->assertHasNoErrors();

        expect(Counterparty::firstOrFail())
            ->phone->toBe('+79171234567')
            ->type->toBe('Ресторан')
            ->cooperation_started_at->format('d.m.Y')->toBe('01.09.2026')
            ->cooperation_terms->toBe("10 %\nоплата после мероприятия");
    });

    it('takes the initial stage from ?stage= and ignores a wrong value', function () {
        Livewire::withQueryParams(['stage' => 'pushing'])->test(CounterpartyForm::class)
            ->assertSet('stage', 'pushing')
            ->set('name', 'Тамада')
            ->call('save');

        Livewire::withQueryParams(['stage' => 'nonsense'])->test(CounterpartyForm::class)->assertSet('stage', 'first_contact');

        $counterparty = Counterparty::firstOrFail();
        expect($counterparty->stage)->toBe(CounterpartyStage::Pushing)
            ->and($counterparty->activityLogs()->pluck('event')->all())->toBe(['created']);
    });

    it('validates the fields', function () {
        Livewire::test(CounterpartyForm::class)->call('save')->assertHasErrors(['name' => 'required']);

        Livewire::test(CounterpartyForm::class)
            ->set('name', 'Агентство')->set('phone', '12345')->set('email', 'не-email')
            ->set('cooperation_started_at', 'вчера')->set('stage', 'nonsense')
            ->call('save')
            ->assertHasErrors(['phone', 'email', 'cooperation_started_at', 'stage']);

        expect(Counterparty::count())->toBe(0);
    });
});

describe('card', function () {
    it('shows the card with the stage, 404 for a missing one', function () {
        $counterparty = Counterparty::factory()->cooperating()->create(['name' => 'Площадка у реки']);

        $this->get(route('counterparties.show', $counterparty))
            ->assertOk()
            ->assertSee('Площадка у реки')
            ->assertSee('Сотрудничаем')
            ->assertSee('Контактных лиц пока нет')
            ->assertSee('Контрагент создан');

        $this->get('/counterparties/999')->assertNotFound();
        $this->get(route('counterparties.create'))->assertOk()->assertSee('Название *');
    });

    it('sends guests to the login page', function () {
        auth()->logout();
        $counterparty = Counterparty::factory()->create();

        $this->get(route('counterparties.show', $counterparty))->assertRedirect(route('login'));
        $this->get(route('counterparties.create'))->assertRedirect(route('login'));
    });

    it('edits fields one by one right on the card', function () {
        $counterparty = Counterparty::factory()->create(['name' => 'Старое', 'phone' => '+79171234567']);

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->call('edit', 'name')
            ->assertSet('value', 'Старое')
            ->set('value', 'Новое')
            ->call('save')
            ->assertSet('editing', null)
            ->call('edit', 'phone')
            ->set('value', '123')
            ->call('save')
            ->assertHasErrors('value')
            ->assertSet('editing', 'phone')
            ->call('edit', 'type')                 // другой щелчок не бросает поле с ошибкой
            ->assertSet('editing', 'phone')
            ->set('value', '8 900 111-22-33')
            ->call('edit', 'type')                 // а исправленное — сохраняет и переходит
            ->assertSet('editing', 'type')
            ->set('value', 'Ресторан')
            ->call('save')
            ->call('edit', 'cooperation_started_at')
            ->set('value', '2026-09-01')
            ->call('save')
            ->call('edit', 'cooperation_started_at')
            ->assertSet('value', '2026-09-01');

        expect($counterparty->fresh())
            ->name->toBe('Новое')
            ->phone->toBe('+79001112233')
            ->type->toBe('Ресторан');
    });

    it('does not edit service fields', function () {
        $counterparty = Counterparty::factory()->create();

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])->call('edit', 'stage')->assertNotFound();
        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])->call('edit', 'position')->assertNotFound();
    });

    it('changes the stage from the card and logs it', function () {
        $counterparty = Counterparty::factory()->create();

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->call('changeStage', 'refused')
            ->assertSee('Этап:')
            ->assertSee('Отказ');

        expect($counterparty->fresh()->stage)->toBe(CounterpartyStage::Refused)
            ->and($counterparty->activityLogs()->where('event', 'updated')->sole()->changes)
            ->toBe(['stage' => ['old' => 'first_contact', 'new' => 'refused']]);

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])->call('changeStage', 'nonsense')->assertNotFound();
    });
});

describe('contacts', function () {
    it('adds contacts on the card, the name is required', function () {
        $counterparty = Counterparty::factory()->create();

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->call('addContact')
            ->call('saveContact')
            ->assertHasErrors(['contact.full_name' => 'required'])
            ->set('contact.full_name', 'Ольга Тестова')
            ->set('contact.phone', '8 900 111-22-33')
            ->set('contact.email', 'olga@example.test')
            ->call('saveContact')
            ->assertHasNoErrors()
            ->assertSet('addingContact', false)
            ->call('addContact')
            ->set('contact.full_name', 'Борис Тестов')
            ->call('saveContact')
            ->assertSee('Добавлено контактное лицо: Борис Тестов');

        expect($counterparty->contacts()->pluck('full_name')->all())->toBe(['Борис Тестов', 'Ольга Тестова']);

        // Контактные лица — вложенные компоненты: проверяем их вид по полной странице.
        $this->get(route('counterparties.show', $counterparty))
            ->assertSeeInOrder(['Борис Тестов', 'Ольга Тестова'])
            ->assertSeeHtml('href="tel:+79001112233"')
            ->assertSeeHtml('href="mailto:olga@example.test"')
            ->assertSee('Добавлено контактное лицо: Ольга Тестова');
    });

    it('edits a contact field by field and logs it', function () {
        $contact = CounterpartyContact::factory()->create(['full_name' => 'Ольга', 'position_title' => null]);

        Livewire::test(CounterpartyContactCard::class, ['contact' => $contact])
            ->call('edit', 'phone')
            ->set('value', '123')
            ->call('save')
            ->assertHasErrors('value')
            ->set('value', '')
            ->call('save')
            ->call('edit', 'position_title')
            ->set('value', 'Менеджер')
            ->call('save')
            ->assertDispatched('contact-changed')
            ->call('edit', 'counterparty_id')
            ->assertNotFound();

        expect($contact->fresh())->position_title->toBe('Менеджер')->phone->toBeNull();

        $this->get(route('counterparties.show', $contact->counterparty))
            ->assertSee('Контактное лицо Ольга:')
            ->assertSee('Должность:');
    });

    it('deletes a contact and logs it', function () {
        $contact = CounterpartyContact::factory()->create(['full_name' => 'Ольга']);

        Livewire::test(CounterpartyContactCard::class, ['contact' => $contact])
            ->assertSeeHtml('wire:confirm')
            ->call('delete')
            ->assertDispatched('contact-changed');

        expect(CounterpartyContact::count())->toBe(0);

        $this->get(route('counterparties.show', $contact->counterparty))
            ->assertSee('Удалено контактное лицо: Ольга')
            ->assertSee('Контактных лиц пока нет');
    });
});

it('escapes user input on the card and keeps line breaks', function () {
    $counterparty = Counterparty::factory()->create([
        'name' => '<script>alert(1)</script>',
        'cooperation_terms' => "<b>жирный</b>\nвторая строка",
    ]);
    $counterparty->contacts()->create(['full_name' => '<img src=x onerror=alert(2)>', 'notes' => "раз\nдва"]);

    $this->get(route('counterparties.show', $counterparty))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<b>жирный</b>', false)
        ->assertDontSee('<img src=x onerror=alert(2)>', false)
        ->assertSee("&lt;b&gt;жирный&lt;/b&gt;\nвторая строка", false)
        ->assertSee("раз\nдва", false);
});
