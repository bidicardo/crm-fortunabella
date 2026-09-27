<?php

namespace Database\Factories;

use App\Models\Counterparty;
use App\Models\CounterpartyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterpartyContact>
 */
class CounterpartyContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'counterparty_id' => Counterparty::factory(),
            'full_name' => fake()->name(),
            'position_title' => fake()->randomElement(['Менеджер', 'Директор', 'Администратор']),
            'phone' => '+79'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
        ];
    }
}
