<?php

namespace Database\Factories;

use App\Models\Communication;
use App\Models\Company;
use App\Models\Lead;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Communication>
 */
class CommunicationFactory extends Factory
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
            'lead_id' => fake()->optional()->randomElement([Lead::factory()]),
            'student_profile_id' => fake()->optional()->randomElement([StudentProfile::factory()]),
            'company_id' => fake()->optional()->randomElement([Company::factory()]),
            'user_id' => fake()->optional()->randomElement([User::factory()]),
            'channel' => fake()->randomElement([
                Communication::CHANNEL_EMAIL,
                Communication::CHANNEL_SMS,
                Communication::CHANNEL_WHATSAPP,
                Communication::CHANNEL_PHONE,
            ]),
            'template_key' => fake()->optional()->randomElement(['lead-follow-up', 'payment-reminder', 'certificate-issued']),
            'subject' => fake()->sentence(6),
            'message' => fake()->paragraph(),
            'status' => fake()->randomElement([
                Communication::STATUS_DRAFT,
                Communication::STATUS_SCHEDULED,
                Communication::STATUS_QUEUED,
                Communication::STATUS_SENT,
                Communication::STATUS_DELIVERED,
                Communication::STATUS_FAILED,
                Communication::STATUS_RETRY,
                Communication::STATUS_OPTED_OUT,
            ]),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+1 week'),
            'queued_at' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'retry_at' => null,
            'retry_count' => 0,
            'failure_reason' => null,
            'opted_out_at' => null,
            'opt_out_reason' => null,
            'metadata' => ['source' => 'system'],
        ];
    }
}
