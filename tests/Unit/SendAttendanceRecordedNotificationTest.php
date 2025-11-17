<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Events\BulkAttendanceRecorded;
use App\Listeners\SendAttendanceRecordedNotification;
use App\Models\AppNotification;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AttendanceRecordedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('notifies admins and assigned teachers when attendance is recorded', function (): void {
    Notification::fake();

    $actor = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $secondaryAdmin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    $teacher = User::factory()->create([
        'user_type' => UserType::Teacher->value,
        'class_name' => 'Grade 4',
        'section' => 'B',
    ]);

    $students = Student::factory()->count(2)->create([
        'class_name' => 'Grade 4',
        'section' => 'B',
        'primary_teacher_id' => $teacher->id,
    ]);

    $event = new BulkAttendanceRecorded(
        actor: $actor,
        attendanceDate: CarbonImmutable::now(),
        students: $students,
        recordCount: $students->count(),
        statusSummary: [
            'present' => $students->count(),
        ],
        className: 'Grade 4',
        section: 'B',
    );

    $listener = app(SendAttendanceRecordedNotification::class);
    $listener->handle($event);

    Notification::assertSentTo($secondaryAdmin, AttendanceRecordedNotification::class);
    Notification::assertSentTo($teacher, AttendanceRecordedNotification::class);
    Notification::assertNotSentTo($actor, AttendanceRecordedNotification::class);

    expect(AppNotification::query()->where('user_id', $secondaryAdmin->id)->where('type', 'attendance.recorded')->exists())->toBeTrue();
    expect(AppNotification::query()->where('user_id', $teacher->id)->where('type', 'attendance.recorded')->exists())->toBeTrue();
    expect(AppNotification::query()->where('user_id', $actor->id)->where('type', 'attendance.recorded')->exists())->toBeFalse();
});
