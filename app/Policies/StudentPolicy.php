<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Automatically grant admins full access.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        return $this->teacherOwnsClass($user, $student);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher() && $user->class_name !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        return $this->teacherOwnsClass($user, $student);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        return $this->teacherOwnsClass($user, $student);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        return $this->teacherOwnsClass($user, $student);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return $this->teacherOwnsClass($user, $student);
    }

    private function teacherOwnsClass(User $user, Student $student): bool
    {
        if (! $user->isTeacher()) {
            return false;
        }

        if ($user->class_name !== $student->class_name) {
            return false;
        }

        if ($user->section === null) {
            return true;
        }

        return $user->section === $student->section;
    }
}
