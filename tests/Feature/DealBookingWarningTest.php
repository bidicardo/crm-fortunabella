<?php

use App\Enums\DealStage;
use App\Livewire\DealBoard;
use App\Livewire\DealShow;
use App\Models\Deal;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('warns after a drag, a list change and the stage line move to booked without paid prepayment', function (string $way) {
    $deal = Deal::factory()->create(['title' => 'Свадьба', 'prepayment_paid' => false]);

    $component = match ($way) {
        'drag' => Livewire::test(DealBoard::class)->call('moveCard', $deal->id, 0, 'booked'),
        'list' => Livewire::test(DealBoard::class)->call('changeStage', $deal->id, 'booked'),
        'card' => Livewire::test(DealShow::class, ['deal' => $deal])->call('changeStage', 'booked'),
    };

    // Переход выполнен, затем — предупреждение (не блокирует).
    expect($deal->fresh()->stage)->toBe(DealStage::Booked);
    $component->assertSet('bookingWarning', '«Свадьба» — в «Брони», но предоплата не получена.')
        ->assertSee('«Свадьба» — в «Брони», но предоплата не получена.')
        ->call('$set', 'bookingWarning', '')->assertDontSee('но предоплата не получена.');
})->with(['drag', 'list', 'card']);

it('does not warn when the prepayment is paid or the stage is not booked', function () {
    $paid = Deal::factory()->create(['prepayment_paid' => true]);
    $other = Deal::factory()->create(['prepayment_paid' => false]);

    Livewire::test(DealBoard::class)->call('moveCard', $paid->id, 0, 'booked')->assertSet('bookingWarning', '')
        ->call('changeStage', $other->id, 'in_work')->assertSet('bookingWarning', '');
    Livewire::test(DealShow::class, ['deal' => $paid->fresh()])->call('changeStage', 'done')->assertSet('bookingWarning', '');

    // Перестановка внутри «Брони» — не переход, предупреждения нет.
    $booked = Deal::factory()->booked()->create(['prepayment_paid' => false]);
    Livewire::test(DealBoard::class)->call('moveCard', $booked->id, 0, 'booked')->assertSet('bookingWarning', '');
});
