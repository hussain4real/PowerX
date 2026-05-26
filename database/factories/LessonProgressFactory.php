<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProgress>
 */
class LessonProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $progress = fake()->numberBetween(0, 100);

        return [
            'enrollment_id' => Enrollment::factory(),
            'lesson_id' => Lesson::factory(),
            'lesson_content_revision' => 1,
            'progress_percentage' => $progress,
            'last_position_seconds' => fake()->numberBetween(0, 3600),
            'started_at' => now()->subMinutes(fake()->numberBetween(5, 90)),
            'completed_at' => $progress >= 100 ? now() : null,
            'metadata' => ['event' => 'factory'],
        ];
    }
}
