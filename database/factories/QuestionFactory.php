<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Question;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'course_id' => Course::factory(),
            'topic' => fake()->randomElement(['Safety', 'Load Calculation', 'Regulations', 'Drawings']),
            'difficulty' => fake()->randomElement(['standard', 'intermediate', 'advanced']),
            'type' => 'single_choice',
            'question_text' => fake()->sentence(12).'?',
            'options' => [
                ['key' => 'A', 'label' => fake()->sentence(5)],
                ['key' => 'B', 'label' => fake()->sentence(5)],
                ['key' => 'C', 'label' => fake()->sentence(5)],
                ['key' => 'D', 'label' => fake()->sentence(5)],
            ],
            'correct_answer' => ['A'],
            'explanation' => fake()->sentence(14),
            'is_active' => true,
            'metadata' => ['reviewed' => true],
        ];
    }
}
