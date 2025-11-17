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
use Illuminate\Database\Eloquent\Builder;
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
    private const DASHBOARD_STATUS_COLORS = [
        AttendanceStatus::Present->value => ['#16a34a', '#16a34a'],
        AttendanceStatus::Absent->value => ['#dc2626', '#dc2626'],
        AttendanceStatus::Late->value => ['#f97316', '#f97316'],
    ];

    /**
     * Persist attendance entries in bulk for the provided user context.
     *
     * @param  array{attendance_date:string,records:array<int, array{student_id:int,status:string,note?:string|null}>}  $payload
     * @return Collection<int, Attendance>
     */
    public function recordBulk(User $user, array $payload): Collection
    {
        $attendanceDate = CarbonImmutable::parse($payload['attendance_date'])->startOfDay();
        $records = collect($payload['records'])
            ->keyBy('student_id')
            ->values();

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
                        'attendance_date' => $attendanceDate->toDateTimeString(),
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
        $this->invalidateDashboardCaches($user, $students, $attendanceDate);
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
        $startWindow = $month->startOfDay();
        $endWindow = $endOfMonth->endOfDay();

        $classFilter = $user->isTeacher() ? $user->class_name : ($filters['class_name'] ?? null);
        $sectionFilter = $user->isTeacher() ? $user->section : ($filters['section'] ?? null);

        $cacheKey = $user->isTeacher()
            ? $this->buildTeacherMonthlyKey($user->getKey(), $month->format('Y-m'))
            : $this->buildAdminMonthlyKey($month->format('Y-m'), $classFilter, $sectionFilter);

        $ttl = (int) config('cache.monthly_report_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($user, $month, $classFilter, $sectionFilter, $startWindow, $endWindow): array {
            $query = Attendance::query()
                ->with(['student'])
                ->whereBetween('attendance_date', [$startWindow->toDateTimeString(), $endWindow->toDateTimeString()])
                ->orderBy('attendance_date');

            $this->applyUserScope($query, $user, $classFilter, $sectionFilter);

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
     * Calculate the present percentage for a student for the provided month.
     */
    public function calculateAttendancePercentage(Student $student, int $month): float
    {
        $startOfMonth = $this->resolveMonthFromInt($month);
        $endOfMonth = $startOfMonth->endOfMonth();

        $totals = Attendance::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->where('student_id', $student->getKey())
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $totalRecords = $totals->sum();

        if ($totalRecords === 0) {
            return 0.0;
        }

        $present = $totals[AttendanceStatus::Present->value] ?? 0;

        return round(($present / $totalRecords) * 100, 2);
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
        $rangeStart = $today->subDays(6)->startOfDay();
        $rangeEnd = $today->endOfDay();
        $cacheKey = $this->buildDashboardCacheKey($user, $today);

        $ttl = (int) config('cache.dashboard_ttl', 60);

        return Cache::remember($cacheKey, $ttl, function () use ($user, $today, $rangeStart, $rangeEnd): array {
            $query = Attendance::query()
                ->whereBetween('attendance_date', [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()])
                ->with('student');

            $this->applyUserScope($query, $user);

            /** @var EloquentCollection<int, Attendance> $rows */
            $rows = $query->get();

            $todayRows = $rows->filter(
                fn (Attendance $attendance): bool => $attendance->attendance_date->isSameDay($today)
            );

            $totalsByStatus = $this->mergeStatusBuckets($this->initializeStatusBuckets(), $todayRows);
            $total = array_sum($totalsByStatus);
            $present = $totalsByStatus[AttendanceStatus::Present->value] ?? 0;
            $presentPercentage = $total > 0 ? round(($present / $total) * 100, 2) : 0.0;

            $weeklyTrend = $this->buildWeeklyTrend($rows, $rangeStart, $today);

            return [
                'date' => $today->toDateString(),
                'total' => $total,
                'totals_by_status' => $totalsByStatus,
                'present_percentage' => $presentPercentage,
                'weekly_trend' => $weeklyTrend,
                'chart' => $this->formatChartData($weeklyTrend),
            ];
        });
    }

    private function invalidateDashboardCaches(User $actor, EloquentCollection $students, CarbonImmutable $attendanceDate): void
    {
        $dates = collect([
            $attendanceDate->toDateString(),
            CarbonImmutable::now()->startOfDay()->toDateString(),
        ])->unique();

        $teacherIds = $students->pluck('primary_teacher_id')
            ->filter()
            ->unique()
            ->values();

        if ($actor->isTeacher()) {
            $teacherIds = $teacherIds->push($actor->getKey())->unique()->values();
        }

        foreach ($dates as $date) {
            Cache::forget(sprintf('dashboard:admin:%s', $date));

            foreach ($teacherIds as $teacherId) {
                Cache::forget(sprintf('dashboard:teacher:%d:%s', $teacherId, $date));
            }
        }
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

    private function applyUserScope(Builder $query, User $user, ?string $classFilter = null, ?string $sectionFilter = null): void
    {
        if ($user->isTeacher()) {
            $query->whereHas('student', function (Builder $studentQuery) use ($user): void {
                $studentQuery->where('class_name', $user->class_name);

                if ($user->section !== null) {
                    $studentQuery->where('section', $user->section);
                }
            });

            return;
        }

        if (! empty($classFilter)) {
            $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_name', $classFilter));
        }

        if (! empty($sectionFilter)) {
            $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('section', $sectionFilter));
        }
    }

    private function initializeStatusBuckets(): array
    {
        return collect(AttendanceStatus::cases())
            ->mapWithKeys(static fn (AttendanceStatus $status): array => [$status->value => 0])
            ->toArray();
    }

    /**
     * @param  iterable<int, Attendance>  $attendances
     */
    private function mergeStatusBuckets(array $buckets, iterable $attendances): array
    {
        foreach ($attendances as $attendance) {
            $status = $this->resolveStatusValue($attendance->status);
            $buckets[$status] = ($buckets[$status] ?? 0) + 1;
        }

        return $buckets;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWeeklyTrend(EloquentCollection $records, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $trend = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $dayRows = $records->filter(
                static fn (Attendance $attendance): bool => $attendance->attendance_date->isSameDay($day)
            );

            $totals = $this->mergeStatusBuckets($this->initializeStatusBuckets(), $dayRows);

            $trend[] = [
                'date' => $day->toDateString(),
                'totals_by_status' => $totals,
                'total' => array_sum($totals),
            ];
        }

        return $trend;
    }

    private function formatChartData(array $weeklyTrend): array
    {
        $labels = array_column($weeklyTrend, 'date');

        $datasets = collect(AttendanceStatus::cases())->map(function (AttendanceStatus $status) use ($weeklyTrend): array {
            $colors = self::DASHBOARD_STATUS_COLORS[$status->value] ?? ['#2563eb', '#2563eb'];

            return [
                'label' => Str::headline($status->value),
                'data' => array_map(
                    static fn (array $day): int => $day['totals_by_status'][$status->value] ?? 0,
                    $weeklyTrend
                ),
                'backgroundColor' => $colors[0],
                'borderColor' => $colors[1],
                'tension' => 0.3,
                'fill' => 'origin',
            ];
        })->values()->all();

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    private function buildDashboardCacheKey(User $user, CarbonImmutable $today): string
    {
        if ($user->isAdmin()) {
            return sprintf('dashboard:admin:%s', $today->toDateString());
        }

        return sprintf('dashboard:teacher:%d:%s', $user->getKey() ?? 0, $today->toDateString());
    }

    private function resolveMonthFromInt(int $month): CarbonImmutable
    {
        $value = (string) $month;

        if (strlen($value) === 6) {
            $year = (int) substr($value, 0, 4);
            $monthNumber = (int) substr($value, -2);
        } else {
            $year = CarbonImmutable::now()->year;
            $monthNumber = $month;
        }

        if ($monthNumber < 1 || $monthNumber > 12) {
            $monthNumber = CarbonImmutable::now()->month;
            $year = CarbonImmutable::now()->year;
        }

        return CarbonImmutable::create($year, $monthNumber, 1)->startOfMonth();
    }
}
