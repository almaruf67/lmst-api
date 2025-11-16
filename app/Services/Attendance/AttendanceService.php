<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Events\BulkAttendanceRecorded;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Attendance service orchestrating bulk capture and reporting.
 *
 * @context Coordinates attendance persistence and reporting with role-aware safeguards
 *
 * @pattern Service layer encapsulating transactional write logic and query aggregation
 */
class AttendanceService
{
    /**
     * Persist attendance entries in bulk for the provided user context.
     *
     * @param  array{attendance_date:string,records:array<int, array{student_id:int,status:string,note?:string|null}>}  $payload
     * @return Collection<int, Attendance>
     */
    public function recordBulk(User $user, array $payload): Collection
    {
        $attendanceDate = CarbonImmutable::parse($payload['attendance_date'])->startOfDay();
        $records = collect($payload['records']);

        if ($records->isEmpty()) {
            return collect();
        }

        /** @var EloquentCollection<int, Student> $students */
        $students = Student::query()
            ->whereIn('id', $records->pluck('student_id')->all())
            ->get()
            ->keyBy('id');

        $this->ensureTeacherAccess($user, $students);

        $saved = DB::transaction(function () use ($records, $attendanceDate, $students, $user): Collection {
            return $records->map(function (array $record) use ($attendanceDate, $students, $user): Attendance {
                $student = $students->get($record['student_id']);

                if (! $student) {
                    throw new AuthorizationException('Unable to locate student for attendance recording.');
                }

                /** @var Attendance $attendance */
                $attendance = Attendance::query()->updateOrCreate(
                    [
                        'student_id' => $student->getKey(),
                        'attendance_date' => $attendanceDate->toDateString(),
                    ],
                    [
                        'status' => $record['status'],
                        'note' => $record['note'] ?? null,
                        'recorded_by' => $user->getKey(),
                    ]
                );

                return $attendance->load('student');
            });
        });

        // Invalidate caches impacted by new attendance
        $this->invalidateDashboardCaches($user);
        $this->invalidateMonthlyReportCaches($user, $students, $attendanceDate);

        $this->dispatchBulkRecordedEvent($user, $attendanceDate, $students, $saved);

        return $saved;
    }

    /**
     * Build a monthly attendance report for the authenticated user or selected class.
     *
     * @param  array{month:string,class_name?:string|null,section?:string|null}  $filters
     * @return array{filters: array{month:string,class_name: ?string,section:?string}, summary: array{total_records:int,totals_by_status: array<string,int>}, daily_totals: array<string,int>, records: EloquentCollection<int, Attendance>}
     */
    public function generateMonthlyReport(User $user, array $filters): array
    {
        $month = CarbonImmutable::createFromFormat('Y-m', $filters['month'])->startOfMonth();
        $endOfMonth = $month->endOfMonth();

        $classFilter = $user->isTeacher() ? $user->class_name : ($filters['class_name'] ?? null);
        $sectionFilter = $user->isTeacher() ? $user->section : ($filters['section'] ?? null);

        $cacheKey = $user->isTeacher()
            ? $this->buildTeacherMonthlyKey($user->getKey(), $month->format('Y-m'))
            : $this->buildAdminMonthlyKey($month->format('Y-m'), $classFilter, $sectionFilter);

        $ttl = (int) config('cache.monthly_report_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($user, $month, $endOfMonth, $classFilter, $sectionFilter): array {
            $query = Attendance::query()
                ->with(['student'])
                ->whereBetween('attendance_date', [$month->toDateString(), $endOfMonth->toDateString()])
                ->orderBy('attendance_date');

            if ($user->isTeacher()) {
                $query->whereHas('student', function ($studentQuery) use ($user): void {
                    $studentQuery->where('class_name', $user->class_name);

                    if ($user->section !== null) {
                        $studentQuery->where('section', $user->section);
                    }
                });
            } else {
                if (! empty($classFilter)) {
                    $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('class_name', $classFilter));
                }

                if (! empty($sectionFilter)) {
                    $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('section', $sectionFilter));
                }
            }

            /** @var EloquentCollection<int, Attendance> $attendances */
            $attendances = $query->get();

            $totalsByStatus = collect(AttendanceStatus::cases())->mapWithKeys(
                static fn (AttendanceStatus $status): array => [$status->value => 0]
            );

            $summaryStatus = $attendances
                ->groupBy(fn (Attendance $attendance): string => $this->resolveStatusValue($attendance->status))
                ->map->count();
            $totalsByStatus = $totalsByStatus->merge($summaryStatus)->toArray();

            $dailyTotals = $attendances->groupBy(fn (Attendance $attendance) => $attendance->attendance_date->toDateString())
                ->map->count()
                ->toArray();

            return [
                'filters' => [
                    'month' => $month->format('Y-m'),
                    'class_name' => $user->isTeacher() ? $user->class_name : $classFilter,
                    'section' => $user->isTeacher() ? $user->section : $sectionFilter,
                ],
                'summary' => [
                    'total_records' => $attendances->count(),
                    'totals_by_status' => $totalsByStatus,
                ],
                'daily_totals' => $dailyTotals,
                'records' => $attendances,
            ];
        });
    }

    /**
     * Ensure a teacher is only targeting their assigned class/section.
     *
     * @param  EloquentCollection<int, Student>  $students
     */
    private function ensureTeacherAccess(User $user, EloquentCollection $students): void
    {
        if ($user->isAdmin()) {
            return;
        }

        foreach ($students as $student) {
            if ($student->class_name !== $user->class_name) {
                throw new AuthorizationException('Teachers may only record attendance for their class.');
            }

            if ($user->section !== null && $student->section !== $user->section) {
                throw new AuthorizationException('Teachers may only record attendance for their section.');
            }
        }
    }

    /**
     * Normalize enum-backed statuses to primitive strings.
     */
    private function resolveStatusValue(mixed $status): string
    {
        if ($status instanceof BackedEnum) {
            return $status->value;
        }

        return (string) $status;
    }

    /**
     * Dispatch event used to fan out notifications on bulk capture.
     *
     * @param  EloquentCollection<int, Student>  $students
     * @param  Collection<int, Attendance>  $records
     */
    private function dispatchBulkRecordedEvent(User $user, CarbonImmutable $attendanceDate, EloquentCollection $students, Collection $records): void
    {
        if ($records->isEmpty()) {
            return;
        }

        $statusSummary = $records
            ->groupBy(fn (Attendance $attendance): string => $this->resolveStatusValue($attendance->status))
            ->map->count()
            ->toArray();

        event(new BulkAttendanceRecorded(
            actor: $user,
            attendanceDate: $attendanceDate,
            students: $students,
            recordCount: $records->count(),
            statusSummary: $statusSummary,
            className: $students->first()?->class_name,
            section: $students->first()?->section,
        ));
    }

    /**
     * Compute today's dashboard summary with caching per user scope.
     *
     * @return array{date:string,total:int,totals_by_status: array<string,int>,present_percentage: float}
     */
    public function getTodayDashboardSummary(User $user): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $cacheKey = $user->isAdmin()
            ? sprintf('dashboard:admin:%s', $today->toDateString())
            : sprintf('dashboard:teacher:%d:%s', $user->getKey(), $today->toDateString());

        $ttl = (int) config('cache.dashboard_ttl', 60);

        return Cache::remember($cacheKey, $ttl, function () use ($user, $today): array {
            $query = Attendance::query()
                ->whereDate('attendance_date', $today->toDateString());

            if ($user->isTeacher()) {
                $query->whereHas('student', function ($studentQuery) use ($user): void {
                    $studentQuery->where('class_name', $user->class_name);
                    if ($user->section !== null) {
                        $studentQuery->where('section', $user->section);
                    }
                });
            }

            /** @var EloquentCollection<int, Attendance> $rows */
            $rows = $query->get();

            $totalsByStatus = collect(AttendanceStatus::cases())
                ->mapWithKeys(static fn (AttendanceStatus $s): array => [$s->value => 0])
                ->merge($rows->groupBy(fn (Attendance $a) => $this->resolveStatusValue($a->status))->map->count())
                ->toArray();

            $total = $rows->count();
            $present = $totalsByStatus[AttendanceStatus::Present->value] ?? 0;
            $presentPercentage = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

            return [
                'date' => $today->toDateString(),
                'total' => $total,
                'totals_by_status' => $totalsByStatus,
                'present_percentage' => $presentPercentage,
            ];
        });
    }

    private function invalidateDashboardCaches(User $user): void
    {
        $date = CarbonImmutable::now()->startOfDay()->toDateString();

        // Admin scope
        Cache::forget(sprintf('dashboard:admin:%s', $date));

        // Current user (teacher or admin acting as recorder)
        Cache::forget(sprintf('dashboard:teacher:%d:%s', $user->getKey(), $date));
    }

    /**
     * Forget cached monthly report slices touched by the write.
     *
     * @param  EloquentCollection<int, Student>  $students
     */
    private function invalidateMonthlyReportCaches(User $actor, EloquentCollection $students, CarbonImmutable $attendanceDate): void
    {
        $month = $attendanceDate->format('Y-m');

        // Global admin summary
        Cache::forget($this->buildAdminMonthlyKey($month, null, null));

        $classCombos = $students->map(fn (Student $student): array => [
            'class_name' => $student->class_name,
            'section' => $student->section,
        ])->unique(fn (array $combo): string => ($combo['class_name'] ?? 'all').'|'.($combo['section'] ?? 'all'));

        foreach ($classCombos as $combo) {
            Cache::forget($this->buildAdminMonthlyKey($month, $combo['class_name'], $combo['section']));
            Cache::forget($this->buildAdminMonthlyKey($month, $combo['class_name'], null));
        }

        $teacherIds = $students->pluck('primary_teacher_id')->filter()->unique()->values();

        if ($actor->isTeacher()) {
            $teacherIds = $teacherIds->push($actor->getKey())->unique()->values();
        }

        foreach ($teacherIds as $teacherId) {
            Cache::forget($this->buildTeacherMonthlyKey((int) $teacherId, $month));
        }
    }

    private function buildAdminMonthlyKey(string $month, ?string $className, ?string $section): string
    {
        return sprintf(
            'attendance:monthly:admin:%s:class:%s:section:%s',
            $month,
            $this->slugValue($className),
            $this->slugValue($section)
        );
    }

    private function buildTeacherMonthlyKey(int $teacherId, string $month): string
    {
        return sprintf('attendance:monthly:teacher:%d:%s', $teacherId, $month);
    }

    private function slugValue(?string $value): string
    {
        return $value ? Str::slug($value) : 'all';
    }
}
