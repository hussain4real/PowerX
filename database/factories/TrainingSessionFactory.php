<?php

namespace Database\Factories;

use App\Models\TrainingBatch;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 20))->setTime(18, 0);

        return [
            'training_batch_id' => TrainingBatch::factory(),
            'title' => fake()->randomElement(['Theory Review', 'Practical Lab', 'Mock Exam', 'Safety Briefing']),
            'session_type' => fake()->randomElement(['theory', 'practical', 'assessment']),
            'venue' => fake()->randomElement(['Doha Training Lab', 'Industrial Area Workshop', 'Online']),
            'status' => 'scheduled',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
            'metadata' => ['attendance_required' => true],
        ];
    }
}
