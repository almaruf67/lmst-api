<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserType;
use App\Events\BulkAttendanceRecorded;
use App\Models\User;
use App\Notifications\AttendanceRecordedNotification;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendAttendanceRecordedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Handle the event.
     */
    public function handle(BulkAttendanceRecorded $event): void
    {
        $recipients = $this->resolveRecipients($event)
            ->reject(static fn(User $user): bool => $user->is($event->actor));

        if ($recipients->isEmpty()) {
            return;
        }

        $payload = [
            'record_count' => $event->recordCount,
            'status_summary' => $event->statusSummary,
            'date' => $event->attendanceDate->toDateString(),
            'class_name' => $event->className,
            'section' => $event->section,
            'actor' => [
                'id' => $event->actor->getKey(),
                'name' => $event->actor->name,
                'type' => $event->actor->user_type?->value,
            ],
        ];

        $message = sprintf(
            '%s recorded attendance for %s%s (%d students).',
            $event->actor->name,
            $event->className ?? 'the selected class',
            $event->section ? ' - Section ' . $event->section : '',
            $event->recordCount,
        );

        Notification::send(
            $recipients,
            new AttendanceRecordedNotification($message, $payload)
        );

        $recipients->each(function (User $recipient) use ($message, $payload): void {
            $this->notificationService->create(
                user: $recipient,
                type: 'attendance.recorded',
                title: 'Attendance Recorded',
                message: $message,
                data: ['context' => $payload],
                priority: 'medium'
            );
        });
    }

    /**
     * Resolve users that should receive the broadcast notification.
     */
    private function resolveRecipients(BulkAttendanceRecorded $event): Collection
    {
        $adminRecipients = User::query()
            ->where('user_type', UserType::Admin->value)
            ->get();

        $teacherIds = $event->students
            ->pluck('primary_teacher_id')
            ->filter()
            ->unique()
            ->values();

        $teacherRecipients = $teacherIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $teacherIds)->get();

        return $adminRecipients->merge($teacherRecipients)->unique('id');
    }
}
