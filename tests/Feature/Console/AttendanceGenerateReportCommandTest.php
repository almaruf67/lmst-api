<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('generates a CSV report for the requested class and month', function (): void {
    Storage::fake('local');

    $className = 'Grade 5';
    $section = 'A';
    $month = now()->format('Y-m');

    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => $className,
        'section' => $section,
    ]);

    $students = Student::factory()->count(2)->create([
        'class_name' => $className,
        'section' => $section,
    ]);

    Attendance::factory()->create([
        'student_id' => $students->first()->id,
        'attendance_date' => CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
        'recorded_by' => $teacher->id,
        'note' => 'Morning assembly duty',
    ]);

    artisan('attendance:generate-report', [
        'month' => $month,
        'class' => $className,
        '--section' => $section,
    ])->assertExitCode(0);

    $expectedPath = sprintf('reports/attendance-%s-%s.csv', Str::slug($className), $month);

    Storage::disk('local')->assertExists($expectedPath);

    $contents = Storage::disk('local')->get($expectedPath);

    expect($contents)
        ->toContain('Grade 5')
        ->toContain($students->first()->student_id)
        ->toContain('Morning assembly duty')
        ->toContain('Totals By Status');
});

it('respects a custom output path option', function (): void {
    Storage::fake('local');

    $className = 'Grade 6';
    $month = now()->format('Y-m');
    $customPath = 'custom/attendance.csv';

    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => $className,
    ]);

    $student = Student::factory()->create([
        'class_name' => $className,
    ]);

    Attendance::factory()->create([
        'student_id' => $student->id,
        'attendance_date' => CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Absent->value,
        'recorded_by' => $teacher->id,
    ]);

    artisan('attendance:generate-report', [
        'month' => $month,
        'class' => $className,
        '--path' => $customPath,
    ])->assertExitCode(0);

    Storage::disk('local')->assertExists($customPath);

    $contents = Storage::disk('local')->get($customPath);

    expect($contents)
        ->toContain(AttendanceStatus::Absent->value)
        ->toContain($student->student_id);
});
