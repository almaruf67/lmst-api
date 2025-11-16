<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User types available in the system.
 *
 * @context Used for simplified RBAC checks (admin vs teacher)
 *
 * @pattern Backed string enum for easy database storage and comparisons
 */
enum UserType: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';

    /**
     * Determine if the enum value represents an administrator.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Determine if the enum value represents a teacher.
     */
    public function isTeacher(): bool
    {
        return $this === self::Teacher;
    }

    /**
     * Return all enum case values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
