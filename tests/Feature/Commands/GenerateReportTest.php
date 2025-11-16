<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

uses(RefreshDatabase::class);

it('generates a CSV attendance report file for the requested month', function (): void {
    Storage::fake('local');

    $teacher = User::factory()->teacher('Grade 7', 'A')->create();
    $student = Student::factory()->create([
        'class_name' => 'Grade 7',
        'section' => 'A',
        'primary_teacher_id' => $teacher->id,
    ]);

    $month = now()->format('Y-m');

    \App\Models\Attendance::factory()->create([
        'student_id' => $student->id,
        'recorded_by' => $teacher->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
    ]);

    $path = 'reports/test-report.csv';

    $this->artisan('attendance:generate-report', [
        'month' => $month,
        'class' => 'Grade 7',
        '--section' => 'A',
        '--path' => $path,
    ])->assertExitCode(SymfonyCommand::SUCCESS);

    Storage::disk('local')->assertExists($path);

    $contents = Storage::disk('local')->get($path);

    expect($contents)
        ->toContain('Grade 7')
        ->toContain('Present')
        ->toContain($student->student_id);
});
