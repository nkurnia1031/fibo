<?php

namespace Database\Factories;

use App\Models\StudentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAccount>
 */
class StudentAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => 'student-'.fake()->unique()->bothify('########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'school' => fake()->company(),
            'status' => 'Aktif',
            'attempt_quota' => 15,
            'attempts_used' => fake()->numberBetween(0, 15),
            'access_expires_at' => now()->addDays(30),
        ];
    }
}
