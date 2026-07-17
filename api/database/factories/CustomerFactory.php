<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('98########'),
            'company' => fake()->company(),
            'notes' => fake()->boolean(40) ? fake()->sentence(12) : null,
            'owner_id' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-6 weeks'),
        ];
    }
}
