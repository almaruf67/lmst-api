<?php

declare(strict_types=1);

namespace App\Support;

class ClassroomOptions
{
    /**
     * Return the configured class list.
     *
     * @return array<int, string>
     */
    public static function classes(): array
    {
        /** @var array<int, string> $classes */
        $classes = config('classroom.classes', []);

        return $classes;
    }

    /**
     * Return the configured section list.
     *
     * @return array<int, string>
     */
    public static function sections(): array
    {
        /** @var array<int, string> $sections */
        $sections = config('classroom.sections', []);

        return $sections;
    }
}
