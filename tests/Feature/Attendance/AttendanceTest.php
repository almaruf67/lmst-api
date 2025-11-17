<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('prevents duplicate attendance rows for the same student and date', function (): void {
    $admin = User::factory()->admin()->create();
    $student = Student::factory()->create();

    Sanctum::actingAs($admin);

    $payload = [
        'attendance_date' => now()->toDateString(),
        'records' => [
            [
                'student_id' => $student->id,
                'status' => AttendanceStatus::Present->value,
            ],
        ],
    ];

    postJson(api('attendance/bulk'), $payload)->assertOk();

    postJson(api('attendance/bulk'), [
        'attendance_date' => $payload['attendance_date'],
        'records' => [
            [
                'student_id' => $student->id,
                'status' => AttendanceStatus::Absent->value,
            ],
        ],
    ])->assertOk();

    expect(Attendance::query()->count())->toBe(1)
        ->and(Attendance::query()->first()->status->value)->toBe(AttendanceStatus::Absent->value);
});

it('filters monthly reports to the teacher class, even when requesting another class', function (): void {
    $teacher = User::factory()->teacher('Grade 5', 'A')->create();
    $ownStudent = Student::factory()->create([
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);
    $otherStudent = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'B',
    ]);

    Attendance::factory()->create([
        'student_id' => $ownStudent->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Late->value,
        'recorded_by' => $teacher->id,
    ]);

    Attendance::factory()->create([
        'student_id' => $otherStudent->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
    ]);

    Sanctum::actingAs($teacher);

    $response = getJson(api('reports/attendance/monthly?month=' . now()->format('Y-m') . '&class_name=Grade%206'));

    $response->assertOk()
        ->assertJsonPath('data.summary.total_records', 1)
        ->assertJsonCount(1, 'data.records');
});

it('caches dashboard summaries for repeat requests', function (): void {
    Cache::flush();

    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $first = getJson(api('dashboard/summary'))->assertOk();
    $firstTotal = $first->json('data.total');

    Attendance::factory()->create([
        'attendance_date' => CarbonImmutable::now()->toDateString(),
        'status' => AttendanceStatus::Present->value,
    ]);

    $second = getJson(api('dashboard/summary'))->assertOk();
    $second->assertJsonPath('data.total', $firstTotal);
});

it('invalidates cached dashboard summaries when recording attendance through the API', function (): void {
    Cache::flush();

    $admin = User::factory()->admin()->create();
    $student = Student::factory()->create();

    Sanctum::actingAs($admin);

    getJson(api('dashboard/summary'))->assertOk();

    postJson(api('attendance/bulk'), [
        'attendance_date' => now()->toDateString(),
        'records' => [
            [
                'student_id' => $student->id,
                'status' => AttendanceStatus::Present->value,
            ],
        ],
    ])->assertOk();

    getJson(api('dashboard/summary'))
        ->assertOk()
        ->assertJsonPath('data.total', 1);
});

it('streams a csv download when requesting the monthly report export', function (): void {
    $admin = User::factory()->admin()->create();
    $student = Student::factory()->create();

    Attendance::factory()->create([
        'student_id' => $student->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Present->value,
    ]);

    Sanctum::actingAs($admin);

    $response = get(api('reports/attendance/monthly?month=' . now()->format('Y-m') . '&format=csv'));

    $response->assertOk();

    expect($response->headers->get('content-type'))
        ->toContain('text/csv')
        ->and($response->streamedContent())
        ->toContain('Student Name');
});

it('returns structured json when exporting the monthly report as json', function (): void {
    $admin = User::factory()->admin()->create();
    $student = Student::factory()->create();

    Attendance::factory()->create([
        'student_id' => $student->id,
        'attendance_date' => now()->startOfMonth()->toDateString(),
        'status' => AttendanceStatus::Late->value,
    ]);

    Sanctum::actingAs($admin);

    $response = get(api('reports/attendance/monthly?month=' . now()->format('Y-m') . '&format=json'));

    $response->assertOk()
        ->assertJson(
            fn(AssertableJson $json) => $json
                ->has('metadata')
                ->has('summary.headers')
                ->has('summary.rows')
                ->has('records.rows', 1)
                ->etc()
        );

    expect($response->headers->get('content-disposition'))
        ->toContain('.json');
});
