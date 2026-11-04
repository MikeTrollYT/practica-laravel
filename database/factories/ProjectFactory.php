<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraphs(3, true),
            'due_date' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'user_id' => User::factory(),
        ];
    }
}
