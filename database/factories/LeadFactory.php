<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Course;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'owner_id' => User::factory(),
            'company_id' => fake()->optional()->randomElement([Company::factory()]),
            'course_id' => fake()->optional()->randomElement([Course::factory()]),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'source' => fake()->randomElement(['website', 'whatsapp', 'referral', 'walk-in']),
            'campaign' => fake()->optional()->word(),
            'status' => fake()->randomElement([
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_QUALIFIED,
                Lead::STATUS_QUOTATION_SENT,
            ]),
            'course_interest' => fake()->randomElement(['Kahramaa exam prep', 'Electrical safety', 'Corporate training']),
            'notes' => fake()->optional()->sentence(12),
            'follow_up_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'outcome' => null,
            'converted_at' => null,
            'metadata' => ['utm_source' => fake()->optional()->word()],
        ];
    }
}
