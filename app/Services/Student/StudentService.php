<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles student listing and mutations.
 *
 * @context Keeps controllers thin and enforces teacher scoping rules
 *
 * @pattern Transactional service that reuses model traits for image handling
 */
class StudentService
{
    public function __construct(private readonly Student $student) {}

    /**
     * Paginate students based on the authenticated user's privileges.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->student->newQuery()->with('primaryTeacher');

        if ($user->isTeacher()) {
            $query->where('class_name', $user->class_name);

            if ($user->section) {
                $query->where('section', $user->section);
            }
        } else {
            if ($class = Arr::get($filters, 'class_name')) {
                $query->where('class_name', $class);
            }

            if ($section = Arr::get($filters, 'section')) {
                $query->where('section', $section);
            }
        }

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->orderBy('name')->paginate($perPage > 0 ? $perPage : 15);
    }

    /**
     * Create a student for the provided user context.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Student
    {
        $attributes = $this->applyTeacherScope($user, $attributes);

        return DB::transaction(function () use ($attributes): Student {
            /** @var UploadedFile|null $photo */
            $photo = $attributes['photo'] ?? null;
            unset($attributes['photo']);

            $student = new Student;
            $student->fill($attributes);

            if ($photo instanceof UploadedFile) {
                $student->photo = $student->uploadAndOptimizeImage($photo, 'students');
            }

            $student->save();

            return $student->fresh(['primaryTeacher']);
        });
    }

    /**
     * Update the given student.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Student $student, User $user, array $attributes): Student
    {
        $attributes = $this->applyTeacherScope($user, $attributes);

        return DB::transaction(function () use ($student, $attributes): Student {
            /** @var UploadedFile|null $photo */
            $photo = $attributes['photo'] ?? null;
            unset($attributes['photo']);

            $student->fill($attributes);

            if ($photo instanceof UploadedFile) {
                $student->photo = $student->replaceImage($photo, $student->photo, 'students');
            }

            $student->save();

            return $student->fresh(['primaryTeacher']);
        });
    }

    /**
     * Delete a student and associated assets.
     */
    public function delete(Student $student): void
    {
        DB::transaction(function () use ($student): void {
            if ($student->photo) {
                $student->deleteImage($student->photo, 'students');
            }

            $student->delete();
        });
    }

    /**
     * Retrieve an entire class roster with recent attendance history.
     *
     * @return Collection<int, Student>
     */
    public function getStudentsByClass(string $className, ?string $section = null): Collection
    {
        $query = $this->student->newQuery()
            ->with([
                'primaryTeacher',
                'attendances' => function ($attendanceQuery): void {
                    $attendanceQuery->latest('attendance_date')->limit(30);
                },
            ])
            ->where('class_name', $className)
            ->orderBy('name');

        if ($section !== null) {
            $query->where('section', $section);
        }

        return $query->get();
    }

    /**
     * Apply teacher scoping to incoming attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function applyTeacherScope(User $user, array $attributes): array
    {
        if ($user->isTeacher()) {
            $attributes['class_name'] = $user->class_name;
            $attributes['section'] = $user->section;
            $attributes['primary_teacher_id'] = $user->id;
        }

        return $attributes;
    }
}
