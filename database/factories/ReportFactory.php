<?php

namespace Database\Factories;

use App\Enums\CounselingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'counselor_id' => fake()->randomNumber(),
            'student_id' => fake()->randomNumber(),
            'reason' => fake()->sentence(4),
            'scheduled_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'room' => 'Ruang BK 1',
            'type' => 'internal',
            'source_type' => 'student',
            'status' => fake()->randomElement(CounselingStatus::cases())->value,
            'priority' => fake()->randomElement(['tinggi', 'rendah']),
            'notes' => fake()->sentence(10),
            'result' => fake()->sentence(10),
        ];
    }
}
