<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-8 weeks');

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('98########'),
            'company' => fake()->company(),
            'source' => fake()->randomElement(Lead::SOURCES),
            'stage' => 'new',
            'deal_value' => fake()->numberBetween(2, 40) * 25000,
            'owner_id' => User::factory(),
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt),
        ];
    }

    public function stage(string $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }
}
