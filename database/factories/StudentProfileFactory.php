<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fullName = fake()->name();

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'full_name' => $fullName,
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'profession' => fake()->randomElement(['Electrical Technician', 'Engineer', 'Supervisor', 'Safety Officer']),
            'qatar_location' => fake()->randomElement(['Doha', 'Al Wakrah', 'Al Khor', 'Industrial Area']),
            'preferred_schedule' => fake()->randomElement(['weekday-evening', 'weekend', 'intensive']),
            'document_status' => 'pending',
            'metadata' => ['nationality' => fake()->country()],
        ];
    }
}
