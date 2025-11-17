<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\NotificationAudience;
use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AppNotification $notification) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.user.'.$this->notification->user_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'data' => $this->notification->data,
            'priority' => $this->notification->priority,
            'audience' => $this->notification->audience instanceof NotificationAudience
                ? $this->notification->audience->value
                : $this->notification->audience,
            'action_url' => $this->notification->action_url,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationCreated';
    }
}
