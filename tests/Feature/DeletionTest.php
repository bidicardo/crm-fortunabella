<?php

use App\Livewire\ClientShow;
use App\Livewire\CounterpartyShow;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Models\User;
use App\Services\ClientMergeService;
use App\Services\RecordDeleter;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

// Обычный участник: удалять может любой пользователь, не только создатель.
beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

/** Подставной счётчик сделок (до Фазы 4 сделок нет). */
function withDeals(int $count): void
{
    app()->instance(RecordDeleter::class, new class($count) extends RecordDeleter
    {
        public function __construct(private int $count) {}

        public function dealsCount(Client|Counterparty $record): int
        {
            return $this->count;
        }
    });
}

describe('client', function () {
    it('deletes a client with its history and returns to the list', function () {
        $client = Client::create(['name' => 'Удаляемый Клиент']);
        $client->update(['phone' => '+79171234567']);
        $other = Client::create(['name' => 'Остающийся Клиент']);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->assertSeeHtml('wire:confirm="Удалить клиента «Удаляемый Клиент» вместе с историей изменений? Восстановить будет нельзя."')
            ->call('destroy')
            ->assertRedirect(route('clients.index'));

        expect(Client::pluck('id')->all())->toBe([$other->id])
            ->and(ActivityLog::where('subject_type', $client->getMorphClass())->pluck('subject_id')->unique()->all())->toBe([$other->id]);

        $this->get(route('clients.show', $client))->assertNotFound();
        $this->get(route('clients.index'))->assertDontSee('Удаляемый Клиент')->assertSee('Остающийся Клиент');
    });

    it('deletes merged duplicates (the whole merge chain) together with the client', function () {
        $service = new ClientMergeService;
        $first = Client::create(['name' => 'Первый дубль']);
        $second = Client::create(['name' => 'Второй дубль']);
        $main = Client::create(['name' => 'Главный']);
        $service->merge($second, $first, [], $this->user);   // первый влит во второй
        $service->merge($main, $second, [], $this->user);    // второй (с первым) — в главного
        $unrelated = Client::create(['name' => 'Посторонний']);

        Livewire::test(ClientShow::class, ['client' => $main])
            ->assertSeeHtml('и влитые в него дубли (2)')
            ->call('destroy');

        expect(Client::pluck('id')->all())->toBe([$unrelated->id])
            ->and(ActivityLog::count())->toBe(1);
    });

    it('has no delete button on an archived duplicate and refuses to delete it', function () {
        $main = Client::create(['name' => 'Главный']);
        $duplicate = Client::create(['name' => 'Дубль']);
        (new ClientMergeService)->merge($main, $duplicate, [], $this->user);

        Livewire::test(ClientShow::class, ['client' => $duplicate->fresh()])
            ->assertDontSeeHtml('wire:click="destroy"')
            ->call('destroy')
            ->assertForbidden();

        expect(Client::count())->toBe(2);
    });

    it('refuses to delete a client with deals and shows why', function () {
        withDeals(2);
        $client = Client::create(['name' => 'Клиент со сделками']);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('destroy')
            ->assertNoRedirect()
            ->assertHasErrors('delete')
            ->assertSee('Сначала удалите или перенесите сделки: 2.');

        expect(Client::count())->toBe(1)->and($client->activityLogs()->count())->toBe(1);
    });

    it('shows a deleted client in someone else\'s history as a name without a link', function () {
        $main = Client::create(['name' => 'Главный']);
        $duplicate = Client::create(['name' => 'Дубль']);
        (new ClientMergeService)->merge($main, $duplicate, [], $this->user);

        // Запись о слиянии осталась у дубля, а главного удалили отдельной строкой (обходной путь).
        Client::whereKey($main->id)->delete();

        $this->get(route('clients.show', $duplicate))
            ->assertOk()
            ->assertSee('Влит в клиента')
            ->assertSee('Главный')
            ->assertSee('(удалён)')
            ->assertDontSee(route('clients.show', $main));
    });
});

describe('counterparty', function () {
    it('deletes a counterparty with contacts and history', function () {
        $counterparty = Counterparty::create(['name' => 'Удаляемое агентство']);
        $counterparty->contacts()->create(['full_name' => 'Ольга']);
        $counterparty->contacts()->create(['full_name' => 'Борис']);
        $other = CounterpartyContact::factory()->create();

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->assertSeeHtml('и его контактных лиц (2)')
            ->call('destroy')
            ->assertRedirect(route('home'));

        expect(Counterparty::pluck('id')->all())->toBe([$other->counterparty_id])
            ->and(CounterpartyContact::pluck('id')->all())->toBe([$other->id])
            ->and(ActivityLog::where('subject_type', $counterparty->getMorphClass())->where('subject_id', $counterparty->id)->count())->toBe(0);

        $this->get(route('counterparties.show', $counterparty))->assertNotFound();
        $this->get(route('home'))->assertSee('Контрагент удалён.');
    });

    it('refuses to delete a counterparty with deals', function () {
        withDeals(1);
        $counterparty = Counterparty::factory()->create();

        Livewire::test(CounterpartyShow::class, ['counterparty' => $counterparty])
            ->call('destroy')
            ->assertHasErrors('delete')
            ->assertSee('Сначала удалите или перенесите сделки: 1.');

        expect(Counterparty::count())->toBe(1);
    });
});

it('does not break on an already deleted record', function () {
    $client = Client::create(['name' => 'Иван']);
    $component = Livewire::test(ClientShow::class, ['client' => $client]);
    Client::whereKey($client->id)->delete();

    // Livewire не находит запись при загрузке компонента — в браузере это ответ 404, ничего не удаляется.
    expect(fn () => $component->call('destroy'))->toThrow(ModelNotFoundException::class);
    expect(Client::count())->toBe(0);
});

it('keeps deletion away from guests', function () {
    auth()->logout();
    $client = Client::factory()->create();

    // Действия Livewire проходят ту же проверку входа, что и страница.
    expect(Livewire::getPersistentMiddleware())->toContain(Authenticate::class);
    $this->get(route('clients.show', $client))->assertRedirect(route('login'));
    expect(Client::count())->toBe(1);
});
