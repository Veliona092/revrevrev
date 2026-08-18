<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassModel>
 */
class ClassModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $classCounter = 1000;
        $classCounter++;

        return [
            'name' => 'Class '.$classCounter,
            'code' => 'CLS'.$classCounter,
            'created_by' => User::factory(),
        ];
    }
}
