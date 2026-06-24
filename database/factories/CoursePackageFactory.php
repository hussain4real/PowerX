<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CoursePackage>
 */
class CoursePackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Essential', 'Professional', 'Corporate', 'Exam Ready']);

        return [
            'team_id' => Team::factory(),
            'course_id' => Course::factory(),
            'name' => $name,
            'slug' => Str::slug($name.' '.fake()->unique()->numberBetween(10, 999)),
            'package_type' => fake()->randomElement(['standard', 'premium', 'corporate']),
            'currency' => 'QAR',
            'price' => fake()->numberBetween(750, 3000),
            'discount_price' => fake()->optional()->numberBetween(650, 2500),
            'validity_days' => 180,
            'max_exam_attempts' => 3,
            'includes_certificate' => true,
            'requires_lesson_completion_for_certificate' => true,
            'requires_exam_pass_for_certificate' => true,
            'requires_attendance_for_certificate' => false,
            'requires_practical_pass_for_certificate' => false,
            'allows_free_preview' => fake()->boolean(30),
            'is_active' => true,
            'metadata' => ['includes_practical' => fake()->boolean()],
        ];
    }
}
