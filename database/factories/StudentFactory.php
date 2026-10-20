<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'email'      => fake()->unique()->safeEmail(),
            'age'        => fake()->numberBetween(15, 25),
            'course'     => fake()->randomElement(['Computer Science', 'Nursing', 'Accountancy']),
            'year_level' => fake()->numberBetween(1, 4),
            'status'     => fake()->randomElement(['active', 'inactive']),
        ];
    }
}