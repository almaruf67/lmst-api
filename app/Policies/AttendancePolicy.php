<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class AttendancePolicy
{
    /**
     * Grant admins full access ahead of ability-specific checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can record attendance entries.
     */
    public function record(User $user): bool
    {
        return $user->isTeacher() && $user->class_name !== null;
    }

    /**
     * Alias for record ability to satisfy resource policy conventions.
     */
    public function create(User $user): bool
    {
        return $this->record($user);
    }

    /**
     * Determine whether the user can review monthly attendance reports.
     */
    public function viewMonthlyReport(User $user): bool
    {
        return $user->isTeacher() && $user->class_name !== null;
    }

    /**
     * Alias for report viewing ability for compatibility with checklist.
     */
    public function viewReport(User $user, string $class): bool
    {
        return $this->viewMonthlyReport($user);
    }

    /**
     * Determine whether the user can view dashboard level summaries.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->isTeacher();
    }
}
