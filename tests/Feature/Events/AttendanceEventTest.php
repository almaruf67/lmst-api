<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Events\BulkAttendanceRecorded;
use App\Listeners\SendAttendanceRecordedNotification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('dispatches the BulkAttendanceRecorded event after successful bulk capture', function (): void {
    Event::fake();

    $admin = User::factory()->admin()->create();
    $students = Student::factory()->count(2)->create();

    Sanctum::actingAs($admin);

    postJson('/api/attendance/bulk', [
        'attendance_date' => now()->toDateString(),
        'records' => $students->map(fn (Student $student): array => [
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present->value,
        ])->all(),
    ])->assertOk();

    Event::assertDispatched(BulkAttendanceRecorded::class, function (BulkAttendanceRecorded $event) use ($students): bool {
        return $event->recordCount === $students->count()
            && $event->students->pluck('id')->sort()->values()->all() === $students->pluck('id')->sort()->values()->all();
    });
});

it('queues the SendAttendanceRecordedNotification listener', function (): void {
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $students = Student::factory()->count(2)->create([
        'class_name' => 'Grade 8',
        'section' => 'A',
        'primary_teacher_id' => User::factory()->teacher('Grade 8', 'A')->create()->id,
    ]);

    Sanctum::actingAs($admin);

    postJson('/api/attendance/bulk', [
        'attendance_date' => now()->toDateString(),
        'records' => $students->map(fn (Student $student): array => [
            'student_id' => $student->id,
            'status' => AttendanceStatus::Late->value,
        ])->all(),
    ])->assertOk();

    Queue::assertPushed(
        CallQueuedListener::class,
        static fn (CallQueuedListener $job): bool => $job->class === SendAttendanceRecordedNotification::class
    );
});
