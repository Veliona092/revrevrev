<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => ClassModel::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'file_path' => null,
            'file_type' => null,
            'order' => fake()->numberBetween(1, 10),
            'is_quiz' => false,
            'is_assignment' => false,
            'is_formal_assessment' => false,
            'time_limit' => 0,
            'passing_grade' => null,
            'visibility' => 'all',
        ];
    }
}
