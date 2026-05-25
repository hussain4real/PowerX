<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Team;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingBatch>
 */
class TrainingBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 45))->setTime(18, 0);

        return [
            'team_id' => Team::factory(),
            'course_id' => Course::factory(),
            'instructor_id' => User::factory(),
            'name' => 'PX-'.fake()->unique()->bothify('###'),
            'delivery_mode' => fake()->randomElement(['classroom', 'online', 'blended']),
            'venue' => fake()->randomElement(['Doha Training Lab', 'Industrial Area Workshop', 'Online']),
            'capacity' => fake()->numberBetween(8, 25),
            'status' => 'scheduled',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addWeeks(2),
            'metadata' => ['language' => 'English'],
        ];
    }
}
