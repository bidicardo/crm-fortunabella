<?php

use App\Livewire\ClientMerge;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientMergeService;
use Livewire\Livewire;

function merge(Client $main, Client $duplicate, array $choices = [], ?User $by = null): void
{
    (new ClientMergeService)->merge($main, $duplicate, $choices, $by ?? User::factory()->create());
}

it('applies the chosen field values to the main client', function () {
    $main = Client::factory()->create(['name' => 'Основной', 'email' => 'main@example.test']);
    $duplicate = Client::factory()->create(['name' => 'Дубль', 'email' => 'dup@example.test']);

    merge($main, $duplicate, ['name' => 'duplicate', 'email' => 'main']);

    expect($main->fresh())->name->toBe('Дубль')->email->toBe('main@example.test');
});

it('fills an empty main field from the duplicate by default', function () {
    $main = Client::factory()->create(['phone' => null, 'social' => null, 'name' => 'Основной']);
    $duplicate = Client::factory()->create(['phone' => '+79171234567', 'social' => '@dup', 'name' => 'Дубль']);

    merge($main, $duplicate);

    expect($main->fresh())->phone->toBe('+79171234567')->social->toBe('@dup')->name->toBe('Основной');
});

it('joins notes by default and by the "both" choice', function () {
    $main = Client::factory()->create(['notes' => 'Заметка 1']);
    $duplicate = Client::factory()->create(['notes' => 'Заметка 2']);

    merge($main, $duplicate);

    expect($main->fresh()->notes)->toBe("Заметка 1\n\nЗаметка 2");
});

it('archives the duplicate with merge history and keeps its data', function () {
    $user = User::factory()->create();
    $main = Client::factory()->create();
    $duplicate = Client::factory()->create(['name' => 'Дубль', 'phone' => '+79170000000']);

    merge($main, $duplicate, ['name' => 'main'], $user);

    $duplicate = $duplicate->fresh();
    expect($duplicate)
        ->isArchived()->toBeTrue()
        ->merged_into_id->toBe($main->id)
        ->merged_by->toBe($user->id)
        ->merged_at->not->toBeNull()
        ->name->toBe('Дубль')
        ->phone->toBe('+79170000000');
    expect(Client::count())->toBe(2);
});

it('rejects merging a client with itself, archived or already merged clients', function () {
    $a = Client::factory()->create();
    $b = Client::factory()->create();
    $archived = Client::factory()->archived()->create();

    expect(fn () => merge($a, $a))->toThrow(DomainException::class, 'самим собой');
    expect(fn () => merge($a, $archived))->toThrow(DomainException::class);
    expect(fn () => merge($archived, $a))->toThrow(DomainException::class);

    merge($a, $b);
    $c = Client::factory()->create();

    expect(fn () => merge($c, $b->fresh()))->toThrow(DomainException::class);
    expect($c->fresh()->isArchived())->toBeFalse();
});

it('rolls everything back on failure', function () {
    $main = Client::factory()->create(['name' => 'Основной']);
    $duplicate = Client::factory()->create(['name' => 'Дубль']);

    $service = new class extends ClientMergeService
    {
        protected function relations(): array
        {
            throw new RuntimeException('сбой переноса');
        }
    };

    expect(fn () => $service->merge($main, $duplicate, ['name' => 'duplicate'], User::factory()->create()))
        ->toThrow(RuntimeException::class);

    expect($main->fresh()->name)->toBe('Основной')
        ->and($duplicate->fresh()->isArchived())->toBeFalse()
        ->and($duplicate->fresh()->merged_into_id)->toBeNull();
});

it('does not allow mass assignment of merge fields', function () {
    $client = Client::create(['name' => 'Иван', 'merged_into_id' => 1, 'merged_by' => 1, 'merged_at' => now()]);

    expect($client->fresh())->merged_into_id->toBeNull()->merged_by->toBeNull()->merged_at->toBeNull();
});

describe('merge screen', function () {
    beforeEach(fn () => $this->actingAs(User::factory()->create()));

    it('is available to a member and lists candidates except the current client', function () {
        $client = Client::factory()->create(['name' => 'Текущий']);
        Client::factory()->create(['name' => 'Кандидат']);
        Client::factory()->archived()->create(['name' => 'Архивный']);

        $this->get(route('clients.merge', $client))->assertOk();

        Livewire::test(ClientMerge::class, ['client' => $client])
            ->assertSee('Кандидат')
            ->assertDontSee('Архивный')
            ->set('search', 'Канд')->assertSee('Кандидат');
    });

    it('merges with the chosen main card and field values', function () {
        $current = Client::factory()->create(['name' => 'Текущий', 'email' => 'cur@example.test', 'notes' => null]);
        $other = Client::factory()->create(['name' => 'Второй', 'email' => 'oth@example.test', 'notes' => 'заметка']);

        Livewire::test(ClientMerge::class, ['client' => $current])
            ->call('pick', $other->id)
            ->assertSet('choices.notes', 'other')
            ->set('mainSide', 'other')
            ->set('choices.email', 'current')
            ->call('merge')
            ->assertRedirect(route('clients.show', $other));

        expect($other->fresh())->email->toBe('cur@example.test')->name->toBe('Второй')
            ->and($current->fresh())->isArchived()->toBeTrue()->merged_into_id->toBe($other->id);
    });

    it('shows counters for related data', function () {
        $current = Client::factory()->create();
        $other = Client::factory()->create();

        Livewire::test(ClientMerge::class, ['client' => $current])->call('pick', $other->id)
            ->assertSee('Сделки')->assertSee('Задачи')->assertSee('Документы');
    });

    it('refuses to pick an archived client or itself', function () {
        $current = Client::factory()->create();
        $archived = Client::factory()->archived()->create();

        Livewire::test(ClientMerge::class, ['client' => $current])
            ->call('pick', $archived->id)->assertSet('otherId', null)
            ->call('pick', $current->id)->assertSet('otherId', null);
    });

    it('is forbidden for an archived client', function () {
        $this->get(route('clients.merge', Client::factory()->archived()->create()))->assertForbidden();
    });

    it('shows merge info on the archived card', function () {
        $main = Client::factory()->create(['name' => 'Главный клиент']);
        $duplicate = Client::factory()->create();
        merge($main, $duplicate, [], User::factory()->create(['name' => 'Мария Тестова']));

        $this->get(route('clients.show', $duplicate))
            ->assertSee('Влит в клиента')->assertSee('Главный клиент')->assertSee('Мария Тестова')
            ->assertSee(route('clients.show', $main), false);
    });

    it('shows the merge button only on active cards', function () {
        $this->get(route('clients.show', Client::factory()->create()))->assertSee('Объединить с…');
        $this->get(route('clients.show', Client::factory()->archived()->create()))->assertDontSee('Объединить с…');
    });
});

it('redirects guests to login', function () {
    $this->get(route('clients.merge', Client::factory()->create()))->assertRedirect('/login');
});
