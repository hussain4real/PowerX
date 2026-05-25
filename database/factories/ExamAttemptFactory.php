<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
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
            'exam_id' => Exam::factory(),
            'enrollment_id' => Enrollment::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'attempt_number' => 1,
            'result' => fake()->randomElement(['pending', 'passed', 'failed']),
            'score' => fake()->randomFloat(2, 45, 100),
            'duration_seconds' => fake()->numberBetween(900, 5400),
            'answers' => [['question_id' => fake()->numberBetween(1, 50), 'answer' => ['A']]],
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'metadata' => ['ip_address' => fake()->ipv4()],
        ];
    }
}
