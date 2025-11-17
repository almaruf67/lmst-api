<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationAudience;
use App\Enums\UserType;
use App\Events\NotificationCreated;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

class NotificationService
{
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'low',
        ?NotificationAudience $audience = null
    ): AppNotification {
        $resolvedAudience = $audience ?? ($user->isAdmin()
            ? NotificationAudience::Admin
            : NotificationAudience::Teacher);

        $notification = AppNotification::create([
            'user_id' => $user->getKey(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'audience' => $resolvedAudience->value,
            'action_url' => $actionUrl,
        ]);

        Event::dispatch(new NotificationCreated($notification));

        return $notification;
    }

    public function createForTeacher(
        User $teacher,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'low'
    ): AppNotification {
        if (! $teacher->isTeacher()) {
            throw new InvalidArgumentException('Only teacher accounts may receive teacher notifications.');
        }

        return $this->create(
            user: $teacher,
            type: $type,
            title: $title,
            message: $message,
            data: $data,
            actionUrl: $actionUrl,
            priority: $priority,
            audience: NotificationAudience::Teacher,
        );
    }

    public function createForTeachers(
        SupportCollection $teachers,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'low'
    ): int {
        $filtered = $teachers->filter(static fn (User $user): bool => $user->isTeacher());

        return $this->createForUsers(
            $filtered,
            $type,
            $title,
            $message,
            $data,
            $actionUrl,
            $priority,
            NotificationAudience::Teacher
        );
    }

    public function createForAdmins(
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'low'
    ): int {
        $admins = User::query()
            ->where('user_type', UserType::Admin->value)
            ->get();

        return $this->createForUsers(
            $admins,
            $type,
            $title,
            $message,
            $data,
            $actionUrl,
            $priority,
            NotificationAudience::Admin
        );
    }

    /**
     * @param  SupportCollection<int, User>  $users
     */
    protected function createForUsers(
        SupportCollection $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'low',
        NotificationAudience $audience = NotificationAudience::Teacher
    ): int {
        if ($users->isEmpty()) {
            return 0;
        }

        $count = 0;
        $users->each(function (User $user) use (
            $type,
            $title,
            $message,
            $data,
            $actionUrl,
            $priority,
            $audience,
            &$count
        ): void {
            $this->create(
                user: $user,
                type: $type,
                title: $title,
                message: $message,
                data: $data,
                actionUrl: $actionUrl,
                priority: $priority,
                audience: $audience
            );

            $count++;
        });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AppNotification::forUser($user->getKey());

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['audience'])) {
            $query->where('audience', $filters['audience']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->where('is_read', false);
            } elseif ($filters['status'] === 'read') {
                $query->where('is_read', true);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    public function recentForUser(User $user, int $limit = 5): Collection
    {
        return AppNotification::forUser($user->getKey())
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function countsForUser(User $user): array
    {
        $baseQuery = AppNotification::forUser($user->getKey());

        $total = (clone $baseQuery)->count();
        $unread = (clone $baseQuery)->where('is_read', false)->count();
        $byPriority = AppNotification::forUser($user->getKey())
            ->selectRaw('priority, COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('priority')
            ->get()
            ->keyBy('priority')
            ->map(fn ($row) => [
                'total' => (int) ($row->total ?? 0),
                'unread' => (int) ($row->unread_count ?? 0),
            ])
            ->toArray();

        $byAudience = AppNotification::forUser($user->getKey())
            ->selectRaw('audience, COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('audience')
            ->get()
            ->keyBy('audience')
            ->map(fn ($row) => [
                'total' => (int) ($row->total ?? 0),
                'unread' => (int) ($row->unread_count ?? 0),
            ])
            ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'by_priority' => $byPriority,
            'by_audience' => $byAudience,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    public function markAsRead(User $user, AppNotification $notification): void
    {
        if ($notification->user_id !== $user->getKey()) {
            throw new AuthorizationException('Not allowed to update this notification');
        }

        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): int
    {
        return AppNotification::forUser($user->getKey())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function markSelectedAsRead(User $user, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return AppNotification::forUser($user->getKey())
            ->whereIn('id', $ids)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
