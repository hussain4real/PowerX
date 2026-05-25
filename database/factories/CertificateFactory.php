<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
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
            'enrollment_id' => Enrollment::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'course_id' => Course::factory(),
            'approved_by_id' => User::factory(),
            'certificate_number' => 'PX-CERT-'.fake()->unique()->numerify('######'),
            'verification_token' => Str::uuid()->toString(),
            'status' => fake()->randomElement(['draft', 'issued']),
            'result' => 'passed',
            'issued_at' => now(),
            'expires_at' => now()->addYears(2),
            'pdf_generated_at' => null,
            'metadata' => ['verification_scope' => 'PowerX training record'],
        ];
    }
}
