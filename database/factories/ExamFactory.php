<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
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
            'course_id' => Course::factory(),
            'title' => fake()->randomElement(['Mock Exam', 'Final Assessment', 'Practice Quiz']).' '.fake()->unique()->numberBetween(1, 99),
            'exam_type' => fake()->randomElement(['mock', 'practice', 'final']),
            'duration_minutes' => fake()->randomElement([30, 60, 90]),
            'pass_mark' => 70,
            'max_attempts' => 3,
            'question_count' => 25,
            'randomize_questions' => true,
            'is_active' => true,
            'metadata' => ['show_results_immediately' => true],
        ];
    }
}
