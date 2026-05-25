<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
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
            'actor_id' => User::factory(),
            'subject_type' => User::class,
            'subject_id' => User::factory(),
            'action' => fake()->randomElement(['payment.approved', 'certificate.issued', 'exam.updated']),
            'summary' => fake()->sentence(),
            'before' => ['status' => 'pending'],
            'after' => ['status' => 'approved'],
            'metadata' => ['source' => 'factory'],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
