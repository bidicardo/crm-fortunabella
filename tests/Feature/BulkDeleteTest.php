<?php

use App\Livewire\ClientTable;
use App\Livewire\CounterpartyTable;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use App\Models\Deal;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('deletes the selected clients with their history and skips the ones with deals', function () {
    [$a, $b] = Client::factory()->count(2)->create();
    $withDeal = Client::factory()->create(['name' => 'Клиент со сделкой']);
    Deal::factory()->for($withDeal)->create();
    $kept = Client::factory()->create();

    Livewire::test(ClientTable::class)
        ->set('selected', [(string) $a->id, (string) $b->id, (string) $withDeal->id, '999'])
        ->assertSee('Выбрано: 4')
        ->assertSeeHtml('wire:confirm="Удалить выбранных клиентов вместе с влитыми в них дублями (4)')
        ->call('deleteSelected')
        ->assertSet('selected', [])
        ->assertSee('Удалено: 2. Не удалены (есть сделки): Клиент со сделкой.');

    expect(Client::pluck('id')->sort()->values()->all())->toBe(collect([$withDeal->id, $kept->id])->sort()->values()->all())
        ->and(ActivityLog::where('subject_type', $a->getMorphClass())->whereIn('subject_id', [$a->id, $b->id])->count())->toBe(0);
});

it('deletes a client together with its merged duplicates; archived ones are not selectable', function () {
    $main = Client::factory()->create();
    $duplicate = Client::factory()->archived()->create(['merged_into_id' => $main->id]);

    // В режиме «Только архивные» флажков нет, и архивный дубль напрямую не удаляется.
    Livewire::test(ClientTable::class)->set('onlyArchived', true)->assertDontSeeHtml('id="select-page"')
        ->set('selected', [(string) $duplicate->id])->call('deleteSelected')->assertSee('Удалено: 0.');
    expect(Client::count())->toBe(2);

    Livewire::test(ClientTable::class)->set('selected', [(string) $main->id])->call('deleteSelected');
    expect(Client::count())->toBe(0);
});

it('selects all rows of the page and deselects them on the second click', function () {
    $ids = Client::factory()->count(3)->create()->pluck('id')->map(fn ($id) => (string) $id)->all();

    // Главный флажок — вызов без параметров: id страницы сервер берёт сам (список из разметки в браузере не доходил).
    $list = Livewire::test(ClientTable::class)->assertSeeHtml('id="select-page"')->assertSeeHtml('wire:click="togglePage"')
        ->call('togglePage');
    expect($list->get('selected'))->toEqualCanonicalizing($ids);

    // Флажки строк отмечены и в разметке: иначе обновление страницы после ответа сервера снимает отметку.
    foreach ($ids as $id) {
        expect($list->html())->toMatch('/<input[^>]*id="select-'.$id.'"[^>]*checked/s');
    }

    $list->call('togglePage')->assertSet('selected', []);

    $list->set('selected', [$ids[0]])->call('togglePage');
    expect($list->get('selected'))->toEqualCanonicalizing($ids);

    $list->call('clearSelection')->assertSet('selected', []);
});

it('deletes the selected counterparties with contacts, in the base and on the active page', function () {
    $a = Counterparty::factory()->create();
    $a->contacts()->create(['full_name' => 'Ольга']);
    $withDeal = Counterparty::factory()->cooperating()->create(['name' => 'Ресторан со сделкой']);
    Deal::factory()->for($withDeal)->create();
    $active = Counterparty::factory()->cooperating()->create();

    Livewire::test(CounterpartyTable::class)
        ->set('selected', [(string) $a->id, (string) $withDeal->id])
        ->assertSeeHtml('wire:confirm="Удалить выбранных контрагентов вместе с контактными лицами (2)')
        ->call('deleteSelected')
        ->assertSee('Удалено: 1. Не удалены (есть сделки): Ресторан со сделкой.');

    Livewire::test(CounterpartyTable::class, ['active' => true])
        ->assertSeeHtml('id="select-'.$active->id.'"')
        ->set('selected', [(string) $active->id])->call('deleteSelected')->assertSee('Удалено: 1.');

    expect(Counterparty::pluck('id')->all())->toBe([$withDeal->id])
        ->and(CounterpartyContact::count())->toBe(0);
});
