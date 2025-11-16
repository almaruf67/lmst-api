<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $className = 'Grade '.fake()->numberBetween(1, 12);

        return [
            'name' => fake()->name(),
            'student_id' => strtoupper(Str::random(3)).'-'.fake()->unique()->numberBetween(1000, 9999),
            'slug' => Str::slug(fake()->unique()->sentence(3).' '.Str::random(4)),
            'class_name' => $className,
            'section' => fake()->randomElement(['A', 'B', 'C']),
            'photo' => null,
            'notes' => fake()->sentence(),
            'primary_teacher_id' => null,
        ];
    }
}
