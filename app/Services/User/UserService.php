<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Provides CRUD helpers for administrator and teacher accounts.
 */
class UserService
{
    public function __construct(private readonly User $user) {}

    /**
     * Paginate users filtered by type and optional criteria.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateByType(UserType $type, array $filters = []): LengthAwarePaginator
    {
        $query = $this->user->newQuery()
            ->where('user_type', $type->value)
            ->orderBy('name');

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($type === UserType::Teacher) {
            if ($class = Arr::get($filters, 'class_name')) {
                $query->where('class_name', $class);
            }

            if ($section = Arr::get($filters, 'section')) {
                $query->where('section', $section);
            }
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    /**
     * Create an administrator record.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAdmin(array $data): User
    {
        $payload = $this->prepareAdminPayload($data);

        return DB::transaction(fn (): User => $this->user->newQuery()->create($payload));
    }

    /**
     * Update an administrator record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAdmin(User $admin, array $data): User
    {
        $payload = $this->prepareAdminPayload($data, false);

        return DB::transaction(function () use ($admin, $payload): User {
            $admin->fill($payload);
            $admin->save();

            return $admin->fresh();
        });
    }

    /**
     * Create a teacher record.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTeacher(array $data): User
    {
        $payload = $this->prepareTeacherPayload($data);

        return DB::transaction(fn (): User => $this->user->newQuery()->create($payload));
    }

    /**
     * Update a teacher record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTeacher(User $teacher, array $data): User
    {
        $payload = $this->prepareTeacherPayload($data, false);

        return DB::transaction(function () use ($teacher, $payload): User {
            $teacher->fill($payload);
            $teacher->save();

            return $teacher->fresh();
        });
    }

    /**
     * Remove a user permanently.
     */
    public function delete(User $user): void
    {
        DB::transaction(static function () use ($user): void {
            $user->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAdminPayload(array $data, bool $creating = true): array
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'user_type' => UserType::Admin->value,
            'employee_code' => $data['employee_code'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        } elseif ($creating) {
            $payload['password'] = $data['password'] ?? null;
        }

        return array_filter(
            $payload,
            static fn ($value) => $value !== null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareTeacherPayload(array $data, bool $creating = true): array
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'class_name' => $data['class_name'] ?? null,
            'section' => $data['section'] ?? null,
            'employee_code' => $data['employee_code'] ?? null,
            'subject_specialization' => $data['subject_specialization'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'date_of_joining' => $data['date_of_joining'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'user_type' => UserType::Teacher->value,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        } elseif ($creating) {
            $payload['password'] = $data['password'] ?? null;
        }

        return array_filter(
            $payload,
            static fn ($value) => $value !== null,
        );
    }
}
