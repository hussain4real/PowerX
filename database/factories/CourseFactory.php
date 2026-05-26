<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement([
            'Kahramaa Electrical Safety Preparation',
            'Electrical Technician Fundamentals',
            'Industrial Control Panel Practical',
            'Power Distribution Systems',
        ]).' '.fake()->unique()->numberBetween(100, 999);

        return [
            'team_id' => Team::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'category' => fake()->randomElement(['Kahramaa', 'Electrical', 'Safety', 'Practical']),
            'status' => 'published',
            'delivery_mode' => fake()->randomElement(['online', 'classroom', 'blended']),
            'currency' => 'QAR',
            'base_price' => fake()->numberBetween(750, 2500),
            'validity_days' => 180,
            'summary' => fake()->sentence(14),
            'description' => fake()->paragraphs(3, true),
            'is_featured' => fake()->boolean(30),
            'metadata' => ['level' => fake()->randomElement(['foundation', 'intermediate', 'advanced'])],
            'published_at' => now(),
            'content_revision' => 1,
            'content_retired_at' => null,
            'replacement_course_id' => null,
            'content_retirement_note' => null,
        ];
    }
}
