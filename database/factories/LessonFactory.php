<?php

namespace Database\Factories;

use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'course_module_id' => CourseModule::factory(),
            'title' => $title,
            'slug' => Str::slug($title.' '.fake()->unique()->numberBetween(10, 999)),
            'lesson_type' => fake()->randomElement(['video', 'document', 'quiz', 'practical']),
            'sort_order' => fake()->numberBetween(1, 12),
            'duration_minutes' => fake()->numberBetween(10, 90),
            'content' => fake()->paragraphs(2, true),
            'is_preview' => fake()->boolean(20),
            'is_active' => true,
            'metadata' => ['requires_completion' => true],
        ];
    }
}
