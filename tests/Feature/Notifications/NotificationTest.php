<?php

declare(strict_types=1);

use App\Enums\NotificationAudience;
use App\Models\AppNotification;
use App\Models\Student;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('lists notifications for the authenticated user only', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    AppNotification::factory()->count(3)->create(['user_id' => $user->id]);
    AppNotification::factory()->count(2)->create();

    $response = $this->getJson(api('notifications'));

    $response->assertOk()
        ->assertJsonPath('message', 'Notifications retrieved successfully')
        ->assertJsonCount(3, 'data.data')
        ->assertJsonPath('data.meta.total', 3);
});

it('returns recent notifications respecting the requested limit', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    AppNotification::factory()->count(6)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->getJson(api('notifications/recent?limit=4'));

    $response->assertOk()
        ->assertJsonCount(4, 'data');
});

it('marks a notification as read for the owner', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    $notification = AppNotification::factory()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    $response = $this->postJson(api("notifications/{$notification->id}/read"));

    $response->assertOk()
        ->assertJsonPath('message', 'Notification marked as read');

    $this->assertDatabaseHas('app_notifications', [
        'id' => $notification->id,
        'is_read' => true,
    ]);
});

it('marks selected notifications as read', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    $notifications = AppNotification::factory()->count(2)->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    $response = $this->postJson(api('notifications/read'), [
        'ids' => $notifications->pluck('id')->all(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.updated', 2);

    foreach ($notifications as $notification) {
        $this->assertDatabaseHas('app_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }
});

it('prevents users from marking notifications they do not own', function (): void {
    /** @var TestCase $this */
    $owner = User::factory()->admin()->create();
    $other = User::factory()->teacher()->create();

    $notification = AppNotification::factory()->create([
        'user_id' => $owner->id,
        'is_read' => false,
    ]);

    Sanctum::actingAs($other);

    $response = $this->postJson(api("notifications/{$notification->id}/read"));

    $response->assertNotFound();
});

it('creates matching audience notifications for every admin', function (): void {
    /** @var TestCase $this */
    $admins = User::factory()->count(3)->admin()->create();
    User::factory()->teacher()->create();

    $service = app(NotificationService::class);

    $created = $service->createForAdmins(
        type: 'system',
        title: 'System Alert',
        message: 'All administrators should see this update.',
        data: ['scope' => 'admins'],
        actionUrl: '/admin/alerts',
        priority: 'high'
    );

    expect($created)->toBe($admins->count());

    foreach ($admins as $admin) {
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'audience' => NotificationAudience::Admin->value,
            'title' => 'System Alert',
        ]);
    }

    $this->assertDatabaseCount('app_notifications', $admins->count());
});

it('flags teacher notifications with the teacher audience', function (): void {
    /** @var TestCase $this */
    $teacher = User::factory()->teacher()->create();

    $service = app(NotificationService::class);

    $notification = $service->createForTeacher(
        teacher: $teacher,
        type: 'attendance',
        title: 'Attendance Reminder',
        message: 'Please submit your attendance sheet.',
        data: ['class' => '5A'],
        priority: 'medium'
    );

    expect($notification->audience)->toBe(NotificationAudience::Teacher);

    $this->assertDatabaseHas('app_notifications', [
        'user_id' => $teacher->id,
        'audience' => NotificationAudience::Teacher->value,
        'title' => 'Attendance Reminder',
    ]);
});

it('returns audience breakdowns when requesting counts', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    AppNotification::factory()->adminAudience()->create([
        'user_id' => $user->id,
        'is_read' => false,
    ]);

    AppNotification::factory()->adminAudience()->create([
        'user_id' => $user->id,
        'is_read' => true,
    ]);

    AppNotification::factory()->create([
        'user_id' => $user->id,
        'audience' => NotificationAudience::Teacher->value,
        'is_read' => false,
    ]);

    $response = $this->getJson(api('notifications/counts'));

    $response->assertOk()
        ->assertJsonPath('data.by_audience.admin.total', 2)
        ->assertJsonPath('data.by_audience.admin.unread', 1)
        ->assertJsonPath('data.by_audience.teacher.total', 1)
        ->assertJsonPath('data.by_audience.teacher.unread', 1);
});

it('notifies the assigned teacher when an admin creates a student', function (): void {
    /** @var TestCase $this */
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher('Grade 5', 'A')->create();

    Sanctum::actingAs($admin);

    $response = $this->postJson(api('students'), [
        'name' => 'Lena Ray',
        'student_id' => 'STD-9001',
        'class_name' => 'Grade 5',
        'section' => 'A',
        'primary_teacher_id' => $teacher->id,
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('app_notifications', [
        'user_id' => $teacher->id,
        'audience' => NotificationAudience::Teacher->value,
        'type' => 'student.created',
    ]);
});

it('notifies all admins when a teacher creates a student', function (): void {
    /** @var TestCase $this */
    $admins = User::factory()->count(2)->admin()->create();
    $teacher = User::factory()->teacher('Grade 4', 'B')->create();

    Sanctum::actingAs($teacher);

    $response = $this->postJson(api('students'), [
        'name' => 'Noah Poe',
        'student_id' => 'STD-9002',
    ]);

    $response->assertCreated();

    foreach ($admins as $admin) {
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'audience' => NotificationAudience::Admin->value,
            'type' => 'student.created',
        ]);
    }
});

it('notifies the assigned teacher when an admin updates a student', function (): void {
    /** @var TestCase $this */
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher('Grade 6', 'C')->create();
    $student = Student::factory()->create([
        'class_name' => 'Grade 6',
        'section' => 'C',
        'primary_teacher_id' => $teacher->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->putJson(api("students/{$student->id}"), [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('app_notifications', [
        'user_id' => $teacher->id,
        'audience' => NotificationAudience::Teacher->value,
        'type' => 'student.updated',
    ]);
});

it('notifies all admins when a teacher updates a student', function (): void {
    /** @var TestCase $this */
    $admins = User::factory()->count(2)->admin()->create();
    $teacher = User::factory()->teacher('Grade 3', 'A')->create();
    $student = Student::factory()->create([
        'class_name' => 'Grade 3',
        'section' => 'A',
        'primary_teacher_id' => $teacher->id,
    ]);

    Sanctum::actingAs($teacher);

    $response = $this->putJson(api("students/{$student->id}"), [
        'notes' => 'Updated notes',
    ]);

    $response->assertOk();

    foreach ($admins as $admin) {
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'audience' => NotificationAudience::Admin->value,
            'type' => 'student.updated',
        ]);
    }
});
