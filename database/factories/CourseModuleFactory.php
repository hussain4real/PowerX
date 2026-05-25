<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseModule>
 */
class CourseModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->randomElement(['Electrical Safety', 'Drawing Review', 'Load Calculation', 'Mock Exam Practice']),
            'summary' => fake()->sentence(12),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'metadata' => ['estimated_hours' => fake()->numberBetween(1, 8)],
        ];
    }
}
