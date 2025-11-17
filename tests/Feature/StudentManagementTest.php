<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows admins to create students with optimized photos', function (): void {
    Storage::fake('public');

    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    Sanctum::actingAs($admin);

    $payload = [
        'name' => 'John Carter',
        'student_id' => 'STD-1001',
        'class_name' => 'Grade 5',
        'section' => 'A',
        'notes' => 'Excellent performance',
        'photo' => UploadedFile::fake()->image('student.jpg', 800, 800),
    ];

    $response = $this->postJson(api('students'), $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'John Carter')
        ->assertJsonPath('data.student_id', 'STD-1001')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'student_id', 'slug', 'class_name', 'section', 'photo_url'],
        ]);

    $student = Student::query()->first();

    expect($student)->not->toBeNull();
    Storage::disk('public')->assertExists("students/{$student->photo}");
});

it('restricts teachers to their own class roster', function (): void {
    $teacherA = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $teacherB = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    $classAStudent = Student::factory()->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $classBStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    Sanctum::actingAs($teacherA);

    $listResponse = $this->getJson(api('students'));
    $listResponse->assertOk();
    expect(collect($listResponse->json('data.students'))->pluck('id'))
        ->toContain($classAStudent->id)
        ->not->toContain($classBStudent->id);

    $this->getJson(api("students/{$classBStudent->id}"))
        ->assertForbidden();

    Sanctum::actingAs($teacherB);

    $this->getJson(api("students/{$classAStudent->id}"))
        ->assertForbidden();
});

it('validates student payloads', function (): void {
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson(api('students'), [
        'name' => '',
        'student_id' => '',
        'class_name' => '',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('data.name.0', 'The name field is required.')
        ->assertJsonPath('data.student_id.0', 'The student id field is required.')
        ->assertJsonPath('data.class_name.0', 'The class name field is required.');
});

it('returns class rosters with attendance history for admins', function (): void {
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $students = Student::factory()->count(2)->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    Attendance::factory()->create([
        'student_id' => $students->first()->id,
        'status' => AttendanceStatus::Present->value,
        'attendance_date' => now()->toDateString(),
        'recorded_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson(api('class-rosters?class_name=Grade%205&section=A'));

    $response->assertOk()
        ->assertJsonPath('data.class_name', 'Grade 5')
        ->assertJsonCount(2, 'data.students');

    $studentsPayload = collect($response->json('data.students'));
    $firstStudentPayload = $studentsPayload->firstWhere('id', $students->first()->id);

    expect($firstStudentPayload['attendances'][0]['status'] ?? null)
        ->toBe(AttendanceStatus::Present->value);
});

it('limits teachers to their assigned class when requesting rosters', function (): void {
    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 4',
        'section' => 'B',
    ]);

    $ownStudent = Student::factory()->create([
        'class_name' => 'Grade 4',
        'section' => 'B',
    ]);

    $otherStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'A',
    ]);

    Sanctum::actingAs($teacher);

    $response = $this->getJson(api('class-rosters?class_name=Grade%206&section=A'));

    $response->assertOk()
        ->assertJsonPath('data.class_name', 'Grade 4')
        ->assertJsonPath('data.section', 'B')
        ->assertJsonCount(1, 'data.students');

    expect(collect($response->json('data.students'))->pluck('id'))
        ->toContain($ownStudent->id)
        ->not->toContain($otherStudent->id);
});
