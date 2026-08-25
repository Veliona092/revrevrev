<?php

namespace Database\Factories;

use App\Models\TestBankQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestBankQuestion>
 */
class TestBankQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'program' => 'accountancy',
            'question_text' => fake()->sentence(),
            'options' => [
                'A' => fake()->word(),
                'B' => fake()->word(),
                'C' => fake()->word(),
                'D' => fake()->word(),
            ],
            'correct_option' => 'A',
            'points' => 1,
            'difficulty' => 'Normal',
            'status' => 'approved',
            'is_archived' => false,
        ];
    }
}
