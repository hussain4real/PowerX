<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'training_session_id' => TrainingSession::factory(),
            'enrollment_id' => Enrollment::factory(),
            'marked_by_id' => User::factory(),
            'assessed_by_id' => fake()->optional()->randomElement([User::factory()]),
            'status' => fake()->randomElement(['present', 'late', 'absent', 'excused']),
            'attended_at' => now(),
            'practical_outcome' => fake()->optional()->randomElement(['passed', 'failed', 'needs_review']),
            'practical_score' => fake()->optional()->randomFloat(2, 50, 100),
            'practical_comments' => fake()->optional()->sentence(12),
            'assessed_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'metadata' => ['notes' => fake()->optional()->sentence()],
        ];
    }
}
