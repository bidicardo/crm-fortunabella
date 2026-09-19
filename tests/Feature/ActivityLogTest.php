<?php

use App\Livewire\ClientShow;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientMergeService;

it('logs creation', function () {
    $client = Client::create(['name' => 'Иван']);

    $log = $client->activityLogs()->sole();
    expect($log)->event->toBe('created')->user_id->toBeNull();
});

it('logs only really changed fields with old and new values', function () {
    $client = Client::create(['name' => 'Иван', 'email' => 'a@example.test', 'notes' => 'заметка']);

    $client->update(['name' => 'Пётр', 'email' => 'a@example.test', 'notes' => 'заметка']);

    $log = $client->activityLogs()->where('event', 'updated')->sole();
    expect($log->changes)->toBe(['name' => ['old' => 'Иван', 'new' => 'Пётр']]);
});

it('does not log when nothing changed', function () {
    $client = Client::create(['name' => 'Иван']);

    $client->update(['name' => 'Иван']);
    $client->touch();

    expect($client->activityLogs()->count())->toBe(1);
});

it('records the current user as the author', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $client = Client::create(['name' => 'Иван']);
    $client->update(['name' => 'Пётр']);

    expect($client->activityLogs()->pluck('user_id')->unique()->all())->toBe([$user->id]);
});

it('never logs hidden fields or service columns', function () {
    $client = Client::create(['name' => 'Иван']);
    $client->makeHidden('notes');

    $client->update(['notes' => 'секрет']);

    expect($client->activityLogs()->where('event', 'updated')->count())->toBe(0);
});

it('shows enum values with Russian labels', function () {
    $client = Client::create(['name' => 'Иван']);
    $client->update(['legal_type' => 'organization']);

    expect($client->activityValue('legal_type', 'organization'))->toBe('Организация')
        ->and($client->activityLabel('legal_type'))->toBe('Тип клиента');
});

it('logs a merge on both cards with chosen values', function () {
    $user = User::factory()->create();
    $main = Client::create(['name' => 'Основной']);
    $duplicate = Client::create(['name' => 'Дубль', 'phone' => '+79171234567']);

    (new ClientMergeService)->merge($main, $duplicate, ['name' => 'main'], $user);

    $onMain = $main->activityLogs()->where('event', 'merged')->sole();
    $onDuplicate = $duplicate->activityLogs()->where('event', 'merged')->sole();

    expect($onMain->changes['merged_client'])->toBe(['id' => $duplicate->id, 'name' => 'Дубль'])
        ->and($onMain->changes['values']['name'])->toBe('Основной')
        ->and($onMain->changes['values']['phone'])->toBe('+79171234567')
        ->and($onMain->user_id)->toBe($user->id)
        ->and($onDuplicate->changes['merged_into'])->toBe(['id' => $main->id, 'name' => 'Основной']);

    // Архивация дубля не создаёт отдельной записи updated.
    expect($duplicate->activityLogs()->where('event', 'updated')->count())->toBe(0);
});

describe('history on the card', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->create(['name' => 'Мария Тестова'])));

    it('shows the history, newest first', function () {
        $client = Client::create(['name' => 'Иван']);
        $client->update(['name' => 'Пётр', 'phone' => '8 917 123-45-67']);

        $this->get(route('clients.show', $client))
            ->assertSee('История изменений')
            ->assertSee('Клиент создан')
            ->assertSee('Мария Тестова')
            ->assertSee('Имя:')
            ->assertSee('+79171234567')
            ->assertSeeInOrder(['Имя:', 'Клиент создан']);
    });

    it('shows the merge events', function () {
        $main = Client::create(['name' => 'Главный']);
        $duplicate = Client::create(['name' => 'Дубль']);
        (new ClientMergeService)->merge($main, $duplicate, [], auth()->user());

        $this->get(route('clients.show', $main))->assertSee('Влит клиент')->assertSee('Дубль');
        $this->get(route('clients.show', $duplicate))->assertSee('Влит в клиента')->assertSee('Главный');
    });

    it('shows 3 records first, then up to 10 in a scrollable block', function () {
        $client = Client::create(['name' => 'Иван']);
        foreach (range(1, 55) as $i) {
            $client->logActivity('updated', ['name' => ['old' => 'a', 'new' => "имя-$i"]]);
        }

        $component = Livewire\Livewire::test(ClientShow::class, ['client' => $client]);

        expect($component->viewData('logs'))->toHaveCount(3);
        $component->assertSee('Показать ещё')->assertDontSee('overflow-y-auto');

        $component->call('showMoreHistory');
        expect($component->viewData('logs'))->toHaveCount(10);
        $component->assertDontSee('Показать ещё')->assertSee('overflow-y-auto')->assertSee('Свернуть');

        $component->call('collapseHistory');
        expect($component->viewData('logs'))->toHaveCount(3);
        $component->assertSee('Показать ещё')->assertDontSee('Свернуть');
    });

    it('escapes values in the history', function () {
        $client = Client::create(['name' => 'Иван']);
        $client->update(['name' => '<script>alert(1)</script>']);

        $this->get(route('clients.show', $client))->assertDontSee('<script>alert(1)</script>', false);
    });
});
