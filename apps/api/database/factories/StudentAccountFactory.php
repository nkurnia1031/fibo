<?php

namespace Database\Factories;

use App\Models\StudentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'password' => Hash::make('student-password'),
            'email_verified_at' => now(),
            'point_balance' => 0,
            'remember_token' => Str::random(10),
            'attempt_quota' => 15,
            'attempts_used' => fake()->numberBetween(0, 15),
            'access_expires_at' => now()->addDays(30),
        ];
    }
}
