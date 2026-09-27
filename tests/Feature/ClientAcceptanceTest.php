<?php

// Задача 20: итоговые проверки Фазы 2 «Клиенты» — сквозной сценарий и пробелы по критериям приёмки
// (docs/20-acceptance-criteria.md, раздел «Клиенты»). Остальные критерии закрыты тестами своих задач.

use App\Livewire\ClientForm;
use App\Livewire\ClientMerge;
use App\Livewire\ClientShow;
use App\Livewire\ClientTable;
use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('runs the whole client flow end to end', function () {
    $this->actingAs(User::factory()->create());

    // Создание с телефоном в «восьмёрочном» написании — в БД нормализованный номер.
    Livewire::test(ClientForm::class)->set('name', 'Анна Первая')->set('phone', '8 917 123-45-67')->call('save');
    $first = Client::where('name', 'Анна Первая')->sole();
    expect($first->phone)->toBe('+79171234567');

    // Тот же номер в другом написании — предупреждение, затем «Продолжить создание».
    Livewire::test(ClientForm::class)
        ->set('name', 'Анна Вторая')->set('phone', '+7 (917) 123-45-67')
        ->call('save')
        ->assertSee('Возможно, такой клиент уже есть')
        ->assertSee('Анна Первая')
        ->call('save', true);
    $second = Client::where('name', 'Анна Вторая')->sole();
    expect(Client::count())->toBe(2);

    // Оба в списке, поиск находит по любому написанию номера.
    foreach (['8 917 123', '+7917123', '9171234567'] as $term) {
        expect(Livewire::test(ClientTable::class)->set('search', $term)->viewData('clients')->pluck('name')->sort()->values()->all())
            ->toBe(['Анна Вторая', 'Анна Первая']);
    }

    // Правка одного поля прямо в карточке второго клиента.
    Livewire::test(ClientShow::class, ['client' => $second])
        ->call('edit', 'role')->set('value', 'Организатор')->call('save')->assertHasNoErrors();

    // Слияние из карточки второго: он основной, первый — дубль.
    Livewire::test(ClientMerge::class, ['client' => $second])
        ->call('pick', $first->id)
        ->assertSet('mainSide', 'current')
        ->call('merge')
        ->assertRedirect(route('clients.show', $second));

    expect($first->fresh()->isArchived())->toBeTrue()
        ->and($first->fresh()->merged_into_id)->toBe($second->id)
        ->and($second->fresh())->phone->toBe('+79171234567')->role->toBe('Организатор');

    // Дубль скрыт из списка по умолчанию, правка архивной карточки недоступна.
    expect(Livewire::test(ClientTable::class)->viewData('clients')->pluck('name')->all())->toBe(['Анна Вторая']);
    Livewire::test(ClientShow::class, ['client' => $first->fresh()])->call('edit', 'role')->assertForbidden();

    // История основной карточки: создание, правка и слияние.
    $this->get(route('clients.show', $second))
        ->assertOk()
        ->assertSee('Клиент создан')
        ->assertSee('Организатор')
        ->assertSee('Влит клиент')
        ->assertSee('Анна Первая');
});

describe('security and integrity', function () {
    it('keeps every client page away from guests and has no edit page', function () {
        $client = Client::factory()->create();

        foreach (['/clients', '/clients/create', "/clients/{$client->id}", "/clients/{$client->id}/merge"] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $this->actingAs(User::factory()->create())->get("/clients/{$client->id}/edit")->assertNotFound();
    });

    it('re-checks the login on every Livewire action of the client pages', function () {
        // Livewire повторяет middleware страницы (группа auth) на каждом запросе действия компонента.
        expect(Livewire::getPersistentMiddleware())->toContain(Authenticate::class);
    });

    it('does not let the card edit service fields, fake the edited field or skip validation', function () {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create(['email' => 'old@example.test']);

        foreach (['archived_at', 'merged_into_id', 'merged_by', 'merged_at', 'id'] as $field) {
            Livewire::test(ClientShow::class, ['client' => $client])->call('edit', $field)->assertNotFound();
        }

        expect(fn () => Livewire::test(ClientShow::class, ['client' => $client])->set('editing', 'archived_at'))
            ->toThrow(CannotUpdateLockedPropertyException::class);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('edit', 'email')->set('value', 'не-email')->call('save')->assertHasErrors('value')
            ->call('edit', 'name')->assertSet('editing', 'email');

        expect($client->fresh()->email)->toBe('old@example.test');
    });

    it('forbids saving on an archived card', function () {
        $this->actingAs(User::factory()->create());

        Livewire::test(ClientShow::class, ['client' => Client::factory()->archived()->create()])->call('save')->assertForbidden();
    });

    it('escapes client fields in the list, the card, the edit mode, the history and the merge screen', function () {
        $this->actingAs(User::factory()->create());
        $xss = '<script>alert(1)</script>';
        $client = Client::factory()->create(['name' => $xss, 'notes' => $xss, 'social' => $xss]);
        $other = Client::factory()->create(['name' => 'Второй', 'notes' => 'другое']);
        $client->update(['role' => $xss]);

        $this->get('/clients')->assertDontSee($xss, false)->assertSee(e($xss), false);
        $this->get(route('clients.show', $client))->assertDontSee($xss, false)->assertSee(e($xss), false);

        Livewire::test(ClientShow::class, ['client' => $client])
            ->call('edit', 'notes')
            ->assertDontSeeHtml($xss)
            ->assertSeeHtml(e($xss));

        Livewire::test(ClientMerge::class, ['client' => $other])
            ->set('search', 'script')->assertDontSeeHtml($xss)
            ->call('pick', $client->id)->assertDontSeeHtml($xss)->assertSeeHtml(e($xss));
    });

    it('renders the list with the same number of queries for 5 and 40 clients (no N+1)', function () {
        $this->actingAs(User::factory()->create());
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/clients')->assertOk();

            return count(DB::getQueryLog());
        };

        Client::factory()->count(5)->create();
        $few = $count();
        Client::factory()->count(35)->create();

        expect($count())->toBe($few);
    });

    it('loads the card history with one query for the feed and one for the authors', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $client = Client::factory()->create();
        foreach (range(1, 5) as $i) {
            $client->update(['role' => "Роль {$i}"]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('clients.show', $client))->assertOk();
        $sql = collect(DB::getQueryLog())->pluck('query');

        expect($sql->filter(fn ($q) => str_contains($q, 'activity_logs'))->count())->toBe(1)
            // Вошедший пользователь + авторы записей одним запросом (без N+1 по записям истории).
            ->and($sql->filter(fn ($q) => preg_match('/from ["`]?users["`]?/', $q))->count())->toBeLessThanOrEqual(2);
    });
});

it('lets the user choose visible columns in the list, keeping the name always on', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/clients')->assertOk()->assertSee('Колонки');

    // Выбор хранится в браузере (Alpine + localStorage); здесь — что каждая скрываемая колонка
    // привязана к своему флажку, а «Имя» не отключается.
    foreach (['phone', 'email', 'social', 'legal_type', 'role', 'created_at', 'updated_at'] as $key) {
        $response->assertSee('id="col-'.$key.'"', false)->assertSee('x-model="cols.'.$key.'"', false)->assertSee('x-show="cols.'.$key.'"', false);
    }

    expect($response->getContent())->toMatch('~<input[^>]*id="col-name"[^>]*disabled~s');
});
