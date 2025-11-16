<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('allows admins to record bulk attendance', function (): void {
    /** @var TestCase $this */
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $students = Student::factory()->count(2)->create();

    Sanctum::actingAs($admin);

    $payload = [
        'attendance_date' => now()->toDateString(),
        'records' => $students->values()->map(function (Student $student, int $index): array {
            return [
                'student_id' => $student->id,
                'status' => $index === 0 ? AttendanceStatus::Present->value : AttendanceStatus::Absent->value,
                'note' => 'Note '.$index,
            ];
        })->all(),
    ];

    $response = $this->postJson('/api/attendance/bulk', $payload);

    $response->assertOk()
        ->assertJsonPath('data.attendances.0.student.id', $students->first()->id);

    $expectedDate = CarbonImmutable::parse($payload['attendance_date'])->format('Y-m-d 00:00:00');

    foreach ($payload['records'] as $record) {
        $this->assertDatabaseHas('attendances', [
            'student_id' => $record['student_id'],
            'attendance_date' => $expectedDate,
            'status' => $record['status'],
        ]);
    }
});

it('prevents unassigned teachers from recording attendance', function (): void {
    /** @var TestCase $this */
    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => null,
        'section' => null,
    ]);

    $student = Student::factory()->create();

    Sanctum::actingAs($teacher);

    $response = $this->postJson('/api/attendance/bulk', [
        'attendance_date' => now()->toDateString(),
        'records' => [
            [
                'student_id' => $student->id,
                'status' => AttendanceStatus::Present->value,
            ],
        ],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('attendances', 0);
});

it('prevents teachers from recording attendance for other classes', function (): void {
    /** @var TestCase $this */
    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $ownStudent = Student::factory()->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $otherStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    Sanctum::actingAs($teacher);

    $response = $this->postJson('/api/attendance/bulk', [
        'attendance_date' => now()->toDateString(),
        'records' => [
            [
                'student_id' => $ownStudent->id,
                'status' => AttendanceStatus::Present->value,
            ],
            [
                'student_id' => $otherStudent->id,
                'status' => AttendanceStatus::Absent->value,
            ],
        ],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('attendances', 0);
});

it('scopes monthly reports to the teacher class', function (): void {
    /** @var TestCase $this */
    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $matchingStudent = Student::factory()->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $otherStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    Attendance::factory()->create([
        'student_id' => $matchingStudent->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Late->value,
        'recorded_by' => $teacher->id,
    ]);

    Attendance::factory()->create([
        'student_id' => $otherStudent->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
    ]);

    Sanctum::actingAs($teacher);

    $response = $this->getJson('/api/reports/attendance/monthly?month='.now()->format('Y-m'));

    $response->assertOk()
        ->assertJsonPath('data.summary.total_records', 1)
        ->assertJsonCount(1, 'data.records');
});

it('caches monthly report responses per scope', function (): void {
    /** @var TestCase $this */
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $student = Student::factory()->create();
    $secondStudent = Student::factory()->create();

    Attendance::factory()->create([
        'student_id' => $student->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
        'recorded_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $firstResponse = $this->getJson('/api/reports/attendance/monthly?month='.now()->format('Y-m'));

    $firstResponse->assertOk()
        ->assertJsonPath('data.summary.total_records', 1);

    Attendance::factory()->create([
        'student_id' => $secondStudent->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Absent->value,
        'recorded_by' => $admin->id,
    ]);

    $secondResponse = $this->getJson('/api/reports/attendance/monthly?month='.now()->format('Y-m'));

    $secondResponse->assertOk()
        ->assertJsonPath('data.summary.total_records', 1);
});

it('invalidates cached monthly report when attendance is recorded', function (): void {
    /** @var TestCase $this */
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $student = Student::factory()->create();

    Attendance::factory()->create([
        'student_id' => $student->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
        'recorded_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/reports/attendance/monthly?month='.now()->format('Y-m'))
        ->assertOk()
        ->assertJsonPath('data.summary.total_records', 1);

    $this->postJson('/api/attendance/bulk', [
        'attendance_date' => now()->toDateString(),
        'records' => [
            [
                'student_id' => $student->id,
                'status' => AttendanceStatus::Absent->value,
            ],
        ],
    ])->assertOk();

    $this->getJson('/api/reports/attendance/monthly?month='.now()->format('Y-m'))
        ->assertOk()
        ->assertJsonPath('data.summary.total_records', 2);
});
