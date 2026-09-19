<?php

namespace Database\Factories;

use App\Enums\ClientLegalType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+79'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'legal_type' => ClientLegalType::Individual,
            'role' => 'Частный заказчик',
        ];
    }

    public function organization(): static
    {
        return $this->state(fn () => [
            'name' => fake()->company(),
            'legal_type' => ClientLegalType::Organization,
            'role' => 'Организатор',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}
