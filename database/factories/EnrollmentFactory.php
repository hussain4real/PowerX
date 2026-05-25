<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePackage;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
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
            'student_profile_id' => StudentProfile::factory(),
            'company_id' => fake()->optional()->randomElement([Company::factory()]),
            'course_id' => Course::factory(),
            'course_package_id' => CoursePackage::factory(),
            'approved_by_id' => fake()->optional()->randomElement([User::factory()]),
            'status' => fake()->randomElement(['pending', 'approved', 'active', 'completed']),
            'payment_status' => fake()->randomElement(['pending', 'partial', 'paid']),
            'access_starts_at' => now(),
            'access_expires_at' => now()->addMonths(6),
            'approved_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'notes' => fake()->optional()->sentence(10),
            'metadata' => ['admission_channel' => fake()->randomElement(['individual', 'corporate'])],
        ];
    }
}
