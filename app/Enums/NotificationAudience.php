<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the intended audience for an application notification.
 */
enum NotificationAudience: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $audience): string => $audience->value, self::cases());
    }
}
