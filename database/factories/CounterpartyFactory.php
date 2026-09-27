<?php

namespace Database\Factories;

use App\Enums\CounterpartyStage;
use App\Models\Counterparty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Counterparty>
 */
class CounterpartyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['Ивент-агентство', 'Тамада', 'Ресторан', 'Ивент-площадка']),
            'phone' => '+79'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
        ];
    }

    public function atStage(CounterpartyStage $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }

    public function firstContact(): static
    {
        return $this->atStage(CounterpartyStage::FirstContact);
    }

    public function pushing(): static
    {
        return $this->atStage(CounterpartyStage::Pushing);
    }

    public function cooperating(): static
    {
        return $this->atStage(CounterpartyStage::Cooperating)->state(fn () => ['cooperation_started_at' => fake()->date()]);
    }

    public function refused(): static
    {
        return $this->atStage(CounterpartyStage::Refused);
    }
}
