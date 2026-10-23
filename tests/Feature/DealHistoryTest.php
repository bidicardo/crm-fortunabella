<?php

use App\Enums\DealStage;
use App\Livewire\DealShow;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Counterparty;
use App\Models\Deal;
use App\Models\User;
use App\Services\RecordDeleter;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

/** Текст развёрнутой ленты истории карточки сделки, одной строкой. */
function dealHistory(Deal $deal): string
{
    $html = Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->set('tab', 'history')->call('showMoreHistory')->html();

    return preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));
}

it('shows every kind of deal value readably in the history', function () {
    $seller = User::factory()->create(['name' => 'Пётр Продавец']);
    $deal = Deal::factory()->create([
        'amount' => null, 'event_date' => null, 'event_time' => null, 'table_types' => null, 'duration_hours' => null,
        'lead_source' => 'avito', 'prepayment_paid' => false, 'guests' => null,
    ]);

    $deal->update(['amount' => 1250000]);
    $deal->update(['event_date' => '2026-12-31', 'event_time' => '19:30']);
    $deal->update(['table_types' => ['blackjack', 'texas']]);
    $deal->update(['duration_hours' => 4]);
    $deal->update(['lead_source_other' => 'Выставка']);
    $deal->update(['lead_source' => 'other']);
    $deal->update(['prepayment_paid' => true]);
    $deal->update(['sold_by_id' => $seller->id]);
    $deal->update(['guests' => 80]);

    $text = dealHistory($deal);

    expect($text)
        ->toContain('Сумма: — → 1 250 000 ₽')
        ->toContain('Дата проведения: — → 31.12.2026')
        ->toContain('Время проведения: — → 19:30')
        ->toContain('Типы столов: — → Блэк-джек, Техас')
        ->toContain('Длительность: — → 4 ч')
        ->toContain('Источник лида: Авито → Другое: Выставка')
        ->toContain('Предоплата оплачена: нет → да')
        ->toContain('Кто продал: — → Пётр Продавец')
        ->toContain('Количество гостей: — → 80');
});

it('keeps the names of a client and a counterparty after they are deleted; existing ones are links', function () {
    [$old, $new] = [Client::factory()->create(['name' => 'Анна Старая']), Client::factory()->create(['name' => 'Борис Новый'])];
    $counterparty = Counterparty::factory()->create(['name' => 'Ресторан Уют']);
    $deal = Deal::factory()->for($old)->create(['counterparty_id' => null]);

    $deal->update(['client_id' => $new->id]);
    $deal->update(['counterparty_id' => $counterparty->id]);

    $html = Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->set('tab', 'history')->html();
    expect($html)->toContain('href="'.route('clients.show', $new).'"')
        ->toContain('href="'.route('counterparties.show', $counterparty).'"');

    // Старый клиент удалён (сделок у него больше нет) — в ленте его имя, но без ссылки.
    app(RecordDeleter::class)->delete($old);
    $html = Livewire::test(DealShow::class, ['deal' => $deal->fresh()])->set('tab', 'history')->html();

    expect(dealHistory($deal))->toContain('Клиент: Анна Старая → Борис Новый')->toContain('Контрагент: — → Ресторан Уют')
        ->and($html)->not->toContain('href="'.route('clients.show', $old->id).'"');
});

it('writes one record per stage change and never the position', function () {
    $deal = Deal::factory()->create();
    Deal::factory()->done()->count(2)->create();

    $deal->moveTo(DealStage::Done);
    $deal->moveTo(DealStage::Done, 0);

    $logs = $deal->activityLogs()->where('event', 'updated')->get();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->changes)->toBe(['stage' => ['old' => 'new', 'new' => 'done']])
        ->and(dealHistory($deal))->toContain('Этап: Новая заявка → Проведена');
});

it('deletes the history together with the deal', function () {
    $deal = Deal::factory()->create();
    $deal->update(['amount' => 12345]);

    app(RecordDeleter::class)->delete($deal);

    expect(ActivityLog::where('subject_type', $deal->getMorphClass())->where('subject_id', $deal->id)->count())->toBe(0);
});
