<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userType = fake()->randomElement(UserType::values());

        $className = $userType === UserType::Teacher->value
            ? 'Grade '.fake()->numberBetween(1, 12)
            : null;

        $section = $className ? fake()->randomElement(['A', 'B', 'C']) : null;

        $isTeacher = $userType === UserType::Teacher->value;
        $dateOfJoining = $isTeacher
            ? fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d')
            : null;

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_type' => $userType,
            'class_name' => $className,
            'section' => $section,
            'phone' => fake()->optional(0.7)->numerify('017########'),
            'employee_code' => $isTeacher ? 'TCH-'.Str::upper(Str::random(6)) : null,
            'subject_specialization' => $isTeacher ? fake()->randomElement(['Mathematics', 'Science', 'English', 'Bangla', 'History']) : null,
            'qualification' => $isTeacher ? fake()->randomElement(['B.Ed', 'M.Ed', 'BSc in Education']) : null,
            'date_of_joining' => $dateOfJoining,
            'emergency_contact_name' => $isTeacher ? fake()->name() : null,
            'emergency_contact_phone' => $isTeacher ? fake()->numerify('018########') : null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an administrator user.
     */
    public function admin(): static
    {
        return $this->state(fn (): array => [
            'user_type' => UserType::Admin->value,
            'class_name' => null,
            'section' => null,
            'employee_code' => null,
            'subject_specialization' => null,
            'qualification' => null,
            'date_of_joining' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
        ]);
    }

    /**
     * Create a teacher assigned to a class/section.
     */
    public function teacher(string $className = 'Grade 5', ?string $section = 'A'): static
    {
        return $this->state(function () use ($className, $section): array {
            return [
                'user_type' => UserType::Teacher->value,
                'class_name' => $className,
                'section' => $section,
                'employee_code' => 'TCH-'.Str::upper(Str::random(6)),
                'subject_specialization' => fake()->randomElement(['Mathematics', 'Science', 'English', 'Bangla', 'History']),
                'qualification' => fake()->randomElement(['B.Ed', 'M.Ed', 'BSc in Education']),
                'date_of_joining' => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
                'emergency_contact_name' => fake()->name(),
                'emergency_contact_phone' => fake()->numerify('018########'),
            ];
        });
    }
}
