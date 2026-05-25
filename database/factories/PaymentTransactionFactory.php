<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
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
            'invoice_id' => Invoice::factory(),
            'company_id' => fake()->optional()->randomElement([Company::factory()]),
            'student_profile_id' => StudentProfile::factory(),
            'approved_by_id' => fake()->optional()->randomElement([User::factory()]),
            'method' => fake()->randomElement(['bank_transfer', 'cash', 'cheque']),
            'provider' => null,
            'reference' => fake()->bothify('PX-PAY-####'),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'currency' => 'QAR',
            'amount' => fake()->numberBetween(750, 3000),
            'paid_at' => now(),
            'approved_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
            'metadata' => ['manual_review' => true],
        ];
    }
}
