<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Enums\LeadSource;
use App\Enums\TableType;
use App\Models\Client;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Свадьба', 'Корпоратив', 'День рождения', 'Юбилей']).' '.fake()->lastName(),
            'client_id' => Client::factory(),
            'amount' => fake()->numberBetween(30, 300) * 1000,
            'event_date' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'event_time' => fake()->randomElement(['18:00', '19:00', '20:00']),
            'table_types' => fake()->randomElements(array_column(TableType::cases(), 'value'), 2),
            'duration_hours' => fake()->numberBetween(2, 6),
            'guests' => fake()->numberBetween(20, 150),
            'lead_source' => fake()->randomElement(LeadSource::cases()),
        ];
    }

    public function atStage(DealStage $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }

    public function inWork(): static
    {
        return $this->atStage(DealStage::InWork);
    }

    public function booked(): static
    {
        return $this->atStage(DealStage::Booked);
    }

    public function done(): static
    {
        return $this->atStage(DealStage::Done);
    }

    public function refused(): static
    {
        return $this->atStage(DealStage::Refused);
    }

    /** Предоплата: сумма и статус (по умолчанию — не оплачена). */
    public function withPrepayment(bool $paid = false): static
    {
        return $this->state(fn (array $attributes) => [
            'prepayment_amount' => (int) (($attributes['amount'] ?? 100000) * 0.3),
            'prepayment_paid' => $paid,
        ]);
    }
}
