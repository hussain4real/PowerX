<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(750, 3000);

        return [
            'team_id' => Team::factory(),
            'enrollment_id' => Enrollment::factory(),
            'company_id' => fake()->optional()->randomElement([Company::factory()]),
            'student_profile_id' => StudentProfile::factory(),
            'number' => 'PX-INV-'.fake()->unique()->numerify('######'),
            'type' => fake()->randomElement(['quotation', 'invoice', 'receipt']),
            'status' => fake()->randomElement(['draft', 'issued', 'paid']),
            'currency' => 'QAR',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $subtotal,
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'paid_at' => null,
            'metadata' => ['payment_terms' => 'Manual payment approval required'],
        ];
    }
}
