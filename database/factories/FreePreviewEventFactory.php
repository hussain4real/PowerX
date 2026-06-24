<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\FreePreviewEvent;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreePreviewEvent>
 */
class FreePreviewEventFactory extends Factory
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
            'user_id' => fake()->optional()->randomElement([User::factory()]),
            'course_id' => Course::factory(),
            'lesson_id' => fake()->optional()->randomElement([Lesson::factory()]),
            'event_type' => fake()->randomElement([FreePreviewEvent::EVENT_STARTED, FreePreviewEvent::EVENT_COMPLETED]),
            'source' => fake()->randomElement(['catalog', 'course_detail', 'campaign']),
            'campaign' => fake()->optional()->word(),
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'occurred_at' => now(),
            'metadata' => [],
        ];
    }
}
