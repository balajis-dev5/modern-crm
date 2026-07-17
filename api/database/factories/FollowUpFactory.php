<?php

namespace Database\Factories;

use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'Call to discuss pricing',
                'Send proposal document',
                'Demo walkthrough',
                'Renewal check-in',
                'Share case study',
                'Confirm requirements',
            ]),
            'due_at' => fake()->dateTimeBetween('-10 days', '+14 days'),
            'assigned_to' => User::factory(),
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'due_at' => fake()->dateTimeBetween('-20 days', '-1 days'),
            'done_at' => fake()->dateTimeBetween('-1 days'),
        ]);
    }
}
