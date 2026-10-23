<?php

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Livewire\ClientShow;
use App\Livewire\DealForm;
use App\Livewire\DealShow;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\Deal;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create(['name' => 'Пётр Продавец'])));

describe('creation', function () {
    it('requires a title and a client, defaults the seller to the current user and opens the card', function () {
        $client = Client::factory()->create(['name' => 'Анна Тестова']);

        $form = Livewire::test(DealForm::class)
            ->assertSet('sold_by_id', (string) $this->user->id)
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'client_id' => 'required']);

        $form->set('title', 'Свадьба Тестовых')
            ->set('clientSearch', 'Анна')->assertSee('Анна Тестова')
            ->call('pickClient', $client->id)
            ->set('amount', '150000')->set('event_date', '2026-12-20')->set('event_time', '19:00')
            ->set('table_types', ['blackjack', 'texas'])
            ->set('durationChoice', 'other')->set('duration_hours', '12')
            ->set('lead_source', 'other')->set('lead_source_other', 'Выставка')
            ->set('prepayment_amount', '30000')->set('prepayment_paid', true)
            ->call('save')
            ->assertHasNoErrors();

        $deal = Deal::sole();
        $form->assertRedirect(route('deals.show', $deal));
        expect($deal)->title->toBe('Свадьба Тестовых')->client_id->toBe($client->id)->amount->toBe(150000)
            ->event_time->toBe('19:00')->table_types->toBe(['blackjack', 'texas'])->duration_hours->toBe(12)
            ->lead_source->toBe(LeadSource::Other)->lead_source_other->toBe('Выставка')
            ->prepayment_paid->toBeTrue()->sold_by_id->toBe($this->user->id)->stage->toBe(DealStage::New);
    });

    it('rejects a duration over 12 hours and a blocked seller', function () {
        $blocked = User::factory()->create(['blocked_at' => now()]);

        Livewire::test(DealForm::class)
            ->set('title', 'Т')->call('pickClient', Client::factory()->create()->id)
            ->set('durationChoice', 'other')->set('duration_hours', '13')
            ->set('sold_by_id', (string) $blocked->id)
            ->call('save')
            ->assertHasErrors(['duration_hours', 'sold_by_id']);

        expect(Deal::count())->toBe(0);
    });

    it('creates a new client on the spot and warns about a duplicate phone', function () {
        $existing = Client::factory()->create(['name' => 'Анна Старая', 'phone' => '+79171234567']);

        $form = Livewire::test(DealForm::class)
            ->set('title', 'Юбилей')
            ->call('startNewClient')
            ->call('save')
            ->assertHasErrors(['newClientName' => 'required'])
            ->set('newClientName', 'Анна Новая')->set('newClientPhone', '8 917 123-45-67')
            ->call('save')
            ->assertSee('Возможно, такой клиент уже есть')
            ->assertSee('Анна Старая')
            ->assertDispatched('duplicates-found');

        expect(Deal::count())->toBe(0);

        // «Всё равно создать» — новый клиент с нормализованным телефоном и сделка у него.
        $form->call('save', true)->assertHasNoErrors();
        $deal = Deal::sole();
        expect($deal->client)->name->toBe('Анна Новая')->phone->toBe('+79171234567')
            ->and($deal->client->id)->not->toBe($existing->id);
    });

    it('lets the user pick the existing client from the duplicate warning', function () {
        $existing = Client::factory()->create(['phone' => '+79171234567']);

        Livewire::test(DealForm::class)
            ->set('title', 'Юбилей')->call('startNewClient')
            ->set('newClientName', 'Анна Новая')->set('newClientPhone', '+79171234567')
            ->call('save')
            ->call('pickClient', $existing->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(Deal::sole()->client_id)->toBe($existing->id)->and(Client::count())->toBe(1);
    });

    it('takes the stage, the client and the counterparty from the address; ignores archived and wrong values', function () {
        $client = Client::factory()->create(['name' => 'Анна Тестова']);
        $counterparty = Counterparty::factory()->create(['name' => 'Ресторан Уют']);

        Livewire::withQueryParams(['stage' => 'booked', 'client' => $client->id, 'counterparty' => $counterparty->id])
            ->test(DealForm::class)
            ->assertSet('stage', 'booked')->assertSet('client_id', $client->id)->assertSet('counterparty_id', $counterparty->id)
            ->assertSee('Анна Тестова')->assertSee('Ресторан Уют');

        Livewire::withQueryParams(['stage' => 'nonsense', 'client' => Client::factory()->archived()->create()->id, 'counterparty' => 999])
            ->test(DealForm::class)
            ->assertSet('stage', 'new')->assertSet('client_id', null)->assertSet('counterparty_id', null);
    });

    it('serves the page to users and sends guests to login', function () {
        $this->get('/deals/create')->assertOk()->assertSee('<title>Новая сделка', false);

        auth()->logout();
        $this->get('/deals/create')->assertRedirect('/login');
    });
});

describe('card', function () {
    it('shows the main info, links and the stage line; 404 for a missing deal; guests go to login', function () {
        $client = Client::factory()->create(['name' => 'Анна Тестова', 'phone' => '+79171234567', 'email' => 'anna@example.test']);
        $counterparty = Counterparty::factory()->create(['name' => 'Ресторан Уют']);
        $deal = Deal::factory()->booked()->for($client)->for($counterparty)->create(['title' => 'Свадьба', 'amount' => 1250000, 'sold_by_id' => $this->user->id]);

        $this->get(route('deals.show', $deal))->assertOk()
            ->assertSee('<title>Сделка', false)
            ->assertSee('Свадьба')->assertSee('1 250 000 ₽')->assertSee('Пётр Продавец')
            ->assertSee('href="'.route('clients.show', $client).'"', false)
            ->assertSee('href="tel:+79171234567"', false)->assertSee('href="mailto:anna@example.test"', false)
            ->assertSee('href="'.route('counterparties.show', $counterparty).'"', false)
            ->assertSeeInOrder(['Новая заявка', 'В работе', 'Бронь', 'Проведена', 'Отказ'])
            ->assertSee('aria-current="step"', false);

        $this->get('/deals/999')->assertNotFound();
        auth()->logout();
        $this->get(route('deals.show', $deal))->assertRedirect('/login');
    });

    it('edits plain fields one by one and logs them', function () {
        $deal = Deal::factory()->create(['event_time' => '18:00']);
        $card = Livewire::test(DealShow::class, ['deal' => $deal]);

        $card->call('edit', 'title')->set('value', 'Новое название')->call('save')->assertHasNoErrors()
            ->call('edit', 'amount')->set('value', '-5')->call('save')->assertHasErrors('value')
            ->set('value', '99000')->call('save')->assertHasNoErrors()
            // Время не меняли — сохраняется без ошибки формата (в БД 18:00:00).
            ->call('edit', 'event_time')->assertSet('value', '18:00')->call('save')->assertHasNoErrors()
            ->call('edit', 'lead_source')->set('value', 'avito')->call('save')
            ->call('edit', 'notes')->set('value', "раз\nдва")->call('save');

        expect($deal->fresh())->title->toBe('Новое название')->amount->toBe(99000)->lead_source->toBe(LeadSource::Avito)->notes->toBe("раз\nдва")
            ->and($deal->activityLogs()->where('event', 'updated')->count())->toBe(4);
    });

    it('changes the client and the counterparty by search, the seller only to an active user', function () {
        $deal = Deal::factory()->create();
        $other = Client::factory()->create(['name' => 'Борис Другой']);
        $counterparty = Counterparty::factory()->create(['name' => 'Агентство']);
        $blocked = User::factory()->create(['blocked_at' => now()]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('edit', 'client_id')->set('clientSearch', 'Борис')->assertSee('Борис Другой')->call('pickClient', $other->id)
            ->call('edit', 'counterparty_id')->call('pickCounterparty', $counterparty->id)
            ->call('edit', 'sold_by_id')->set('value', (string) $blocked->id)->call('save')->assertHasErrors('value');

        expect($deal->fresh())->client_id->toBe($other->id)->counterparty_id->toBe($counterparty->id);

        Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->call('edit', 'counterparty_id')->call('clearCounterparty');
        expect($deal->fresh()->counterparty_id)->toBeNull();

        // Выбор клиента работает только в режиме его правки.
        Livewire::test(DealShow::class, ['deal' => $deal])->call('pickClient', $other->id)->assertNotFound();
    });

    it('edits the duration from the list or as another number up to 12', function () {
        $deal = Deal::factory()->create(['duration_hours' => 11]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('editDuration')->assertSet('durationChoice', 'other')
            ->set('durationChoice', '4')->assertSet('editing', null);
        expect($deal->fresh()->duration_hours)->toBe(4);

        Livewire::test(DealShow::class, ['deal' => $deal->fresh()])
            ->call('editDuration')->assertSet('durationChoice', '4')
            ->set('durationChoice', 'other')->set('value', '13')->call('save')->assertHasErrors('value')
            ->set('value', '12')->call('save')->assertHasNoErrors();
        expect($deal->fresh()->duration_hours)->toBe(12);
    });

    it('edits table types with checkboxes and toggles the prepayment', function () {
        $deal = Deal::factory()->create(['table_types' => ['texas'], 'prepayment_paid' => false]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('editTableTypes')->assertSet('tableTypes', ['texas'])
            ->set('tableTypes', ['texas', 'blackjack'])->call('saveTableTypes')
            ->call('editTableTypes')->set('tableTypes', ['poker'])->call('saveTableTypes')->assertHasErrors('tableTypes.0')
            ->call('togglePrepaymentPaid');

        expect($deal->fresh())->table_types->toBe(['blackjack', 'texas'])->prepayment_paid->toBeTrue();
    });

    it('does not edit service fields or let the client fake the edited field', function () {
        $deal = Deal::factory()->create();

        foreach (['stage', 'position', 'id', 'table_types', 'prepayment_paid', 'created_at'] as $field) {
            Livewire::test(DealShow::class, ['deal' => $deal])->call('edit', $field)->assertNotFound();
        }

        expect(fn () => Livewire::test(DealShow::class, ['deal' => $deal])->set('editing', 'stage'))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });

    it('changes the stage from the stage line and logs it; rejects a wrong stage', function () {
        $deal = Deal::factory()->create();

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->call('changeStage', 'done')
            ->call('changeStage', 'nonsense')->assertNotFound();

        expect($deal->fresh()->stage)->toBe(DealStage::Done)
            ->and($deal->activityLogs()->where('event', 'updated')->sole()->changes)->toBe(['stage' => ['old' => 'new', 'new' => 'done']]);
    });

    it('switches tabs; tasks and documents are placeholders; a wrong tab falls back to details', function () {
        $deal = Deal::factory()->create();
        $deal->update(['notes' => 'Важное примечание']);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->assertSee('Типы столов')
            ->set('tab', 'history')->assertSee('История изменений')->assertSee('Важное примечание')
            ->set('tab', 'tasks')->assertSee('Появится позже')
            ->set('tab', 'documents')->assertSee('Появится позже')
            ->set('tab', 'nonsense')->assertSee('Типы столов');

        $this->get(route('deals.show', ['deal' => $deal, 'tab' => 'notes']))->assertSee('Важное примечание');
    });

    it('deletes a deal with its history after confirmation and returns home with a message', function () {
        $deal = Deal::factory()->create(['title' => 'Удаляемая']);
        $deal->update(['amount' => 1000]);

        Livewire::test(DealShow::class, ['deal' => $deal])
            ->assertSeeHtml('wire:confirm="Удалить сделку «Удаляемая» вместе с историей изменений?')
            ->call('destroy')
            ->assertRedirect(route('home'));

        expect(Deal::count())->toBe(0)
            ->and(ActivityLog::where('subject_type', $deal->getMorphClass())->count())->toBe(0);
        $this->get(route('home'))->assertSee('Сделка удалена.');
    });

    it('escapes user input on the card', function () {
        $xss = '<script>alert(1)</script>';
        $deal = Deal::factory()->for(Client::factory()->state(['name' => $xss]))->create(['title' => $xss, 'event_kind' => $xss, 'address' => $xss]);

        $this->get(route('deals.show', $deal))->assertDontSee($xss, false)->assertSee(e($xss), false);
    });
});

describe('deals on client and counterparty cards', function () {
    it('lists the deals and links to a new deal with the record preselected', function () {
        $client = Client::factory()->create();
        $counterparty = Counterparty::factory()->create();
        $deal = Deal::factory()->booked()->for($client)->for($counterparty)->create(['title' => 'Свадьба', 'amount' => 200000, 'event_date' => '2026-12-20']);

        foreach ([route('clients.show', $client), route('counterparties.show', $counterparty)] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('href="'.route('deals.show', $deal).'"', false)
                ->assertSee('Свадьба')->assertSee('Бронь')->assertSee('20.12.2026 · 200 000 ₽');
        }

        $this->get(route('clients.show', $client))->assertSee('href="'.route('deals.create', ['client' => $client->id]).'"', false);
        $this->get(route('counterparties.show', $counterparty))->assertSee('href="'.route('deals.create', ['counterparty' => $counterparty->id]).'"', false);
    });

    it('shows an empty block, no block on an archived duplicate, and refuses to delete a client with deals', function () {
        $client = Client::factory()->create();
        $this->get(route('clients.show', $client))->assertSee('Сделок пока нет.');
        $this->get(route('clients.show', Client::factory()->archived()->create()))->assertDontSee('Сделок пока нет.');

        Deal::factory()->for($client)->create();
        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('destroy')->assertSee('Сначала удалите или перенесите сделки: 1.');
    });
});
