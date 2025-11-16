<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::query()->get();
        if ($students->isEmpty()) {
            return;
        }

        $recorders = User::query()->pluck('id');
        if ($recorders->isEmpty()) {
            $recorders = Collection::make([
                User::factory()->admin()->create()->getKey(),
            ]);
        }

        $dates = Collection::times(7, static function (int $day): CarbonImmutable {
            return CarbonImmutable::now()->subDays($day)->startOfDay();
        });

        $statuses = Collection::make(AttendanceStatus::cases());

        $students->each(function (Student $student) use ($dates, $statuses, $recorders): void {
            $dates->each(function (CarbonImmutable $date) use ($student, $statuses, $recorders): void {
                Attendance::query()->updateOrCreate(
                    [
                        'student_id' => $student->getKey(),
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'status' => $statuses->random()->value,
                        'note' => fake()->boolean(20) ? fake()->sentence(6) : null,
                        'recorded_by' => $student->primary_teacher_id ?? $recorders->random(),
                    ]
                );
            });
        });
    }
}
