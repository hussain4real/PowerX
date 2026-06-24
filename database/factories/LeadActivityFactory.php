<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
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
            'lead_id' => Lead::factory(),
            'actor_id' => User::factory(),
            'type' => fake()->randomElement([
                LeadActivity::TYPE_CONTACT_LOGGED,
                LeadActivity::TYPE_QUALIFIED,
                LeadActivity::TYPE_STATUS_CHANGED,
            ]),
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(10),
            'channel' => fake()->optional()->randomElement(['phone', 'email', 'whatsapp']),
            'previous_status' => 'new',
            'next_status' => 'contacted',
            'follow_up_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'metadata' => [],
        ];
    }
}
