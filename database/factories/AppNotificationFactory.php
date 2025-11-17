<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationAudience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppNotification>
 */
class AppNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isRead = fake()->boolean(40);

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['system', 'attendance', 'report']),
            'title' => fake()->sentence(4),
            'message' => fake()->sentences(2, true),
            'data' => [
                'context' => fake()->words(3),
                'class_name' => fake()->randomElement(['5A', '5B', '6C']),
            ],
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'audience' => NotificationAudience::Teacher->value,
            'action_url' => fake()->optional()->url(),
            'is_read' => $isRead,
            'read_at' => $isRead ? now() : null,
        ];
    }

    public function adminAudience(): self
    {
        return $this->state([
            'audience' => NotificationAudience::Admin->value,
        ]);
    }
}
