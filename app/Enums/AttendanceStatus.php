<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Attendance status enum.
 *
 * @context Used by attendance service, validation rules, and database schema
 *
 * @pattern Backed string enum to keep validation, storage, and responses in sync
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';

    /**
     * Return all enum values for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
