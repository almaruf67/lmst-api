<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('allows admins to view all students with pagination metadata', function (): void {
    $admin = User::factory()->admin()->create();
    $students = Student::factory()->count(3)->create();

    Sanctum::actingAs($admin);

    $response = getJson('/api/students?per_page=50');

    $response->assertOk()
        ->assertJsonPath('data.meta.total', $students->count());

    $ids = collect($response->json('data.students'))->pluck('id');
    expect($ids)->toContain(...$students->modelKeys());
});

it('allows teachers to view only students from their class roster', function (): void {
    $teacher = User::factory()->teacher('Grade 5', 'A')->create();

    $ownStudent = Student::factory()->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $otherStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    Sanctum::actingAs($teacher);

    $response = getJson('/api/students');
    $response->assertOk();

    expect(collect($response->json('data.students'))->pluck('id'))
        ->toContain($ownStudent->id)
        ->not->toContain($otherStudent->id);
});

it('prevents teachers from accessing students outside their assignment', function (): void {
    $teacher = User::factory()->teacher('Grade 4', 'B')->create();

    $student = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'A',
    ]);

    Sanctum::actingAs($teacher);

    getJson("/api/students/{$student->id}")
        ->assertForbidden();
});

it('allows admins to create students with optimized photos and generated slugs', function (): void {
    Storage::fake('public');

    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    Sanctum::actingAs($admin);

    $payload = [
        'name' => 'Jane Carter',
        'student_id' => 'STD-2001',
        'class_name' => 'Grade 5',
        'section' => 'A',
        'photo' => UploadedFile::fake()->image('student.jpg', 1200, 1200),
    ];

    $response = postJson('/api/students', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.slug', Str::slug('Jane Carter STD-2001'))
        ->assertJsonPath('data.photo_url', fn ($value) => $value !== null);

    $student = Student::query()->first();

    expect($student)->not->toBeNull();
    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->assertExists("students/{$student->photo}");
});

it('prevents unassigned teachers from creating students', function (): void {
    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => null,
        'section' => null,
    ]);

    Sanctum::actingAs($teacher);

    postJson('/api/students', [
        'name' => 'Sam Lee',
        'student_id' => 'STD-3001',
        'class_name' => 'Grade 3',
    ])->assertForbidden();
});

it('generates unique slugs even when student names repeat', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $name = 'Repeat Name';

    postJson('/api/students', [
        'name' => $name,
        'student_id' => 'STD-4001',
        'class_name' => 'Grade 2',
    ])->assertCreated();

    postJson('/api/students', [
        'name' => $name,
        'student_id' => 'STD-4002',
        'class_name' => 'Grade 2',
    ])->assertCreated();

    $slugs = Student::query()
        ->where('name', $name)
        ->pluck('slug');

    expect($slugs->unique()->count())->toBe(2);
});

it('returns class rosters with limited attendance history for admins', function (): void {
    $admin = User::factory()->admin()->create();
    $students = Student::factory()->count(2)->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    $student = $students->first();

    foreach (range(0, 34) as $day) {
        Attendance::factory()->create([
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present->value,
            'attendance_date' => CarbonImmutable::now()->subDays($day)->toDateString(),
        ]);
    }

    Sanctum::actingAs($admin);

    $response = getJson('/api/class-rosters?class_name=Grade%205&section=A');

    $response->assertOk()
        ->assertJsonPath('data.class_name', 'Grade 5');

    $firstStudent = collect($response->json('data.students'))->firstWhere('id', $students->first()->id);

    expect($firstStudent['attendances'] ?? [])
        ->toHaveCount(30); // limited by service
});

it('limits teachers to their assigned class when requesting rosters', function (): void {
    $teacher = User::factory()->teacher('Grade 4', 'B')->create();

    $ownStudent = Student::factory()->create([
        'class_name' => 'Grade 4',
        'section' => 'B',
    ]);

    Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'A',
    ]);

    Sanctum::actingAs($teacher);

    $response = getJson('/api/class-rosters?class_name=Grade%206&section=A');

    $response->assertOk()
        ->assertJsonPath('data.class_name', 'Grade 4')
        ->assertJsonCount(1, 'data.students');

    expect(collect($response->json('data.students'))->pluck('id'))
        ->toContain($ownStudent->id);
});
