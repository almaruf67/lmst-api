<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\NotificationAudience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read string $message
 * @property-read string $priority
 * @property-read string|null $action_url
 * @property-read array|null $data
 * @property-read bool $is_read
 * @property-read \Illuminate\Support\Carbon|null $read_at
 * @property-read \Illuminate\Support\Carbon|null $created_at
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'type' => $this->type,
            'audience' => $this->audience instanceof NotificationAudience
                ? $this->audience->value
                : $this->audience,
            'context' => $this->data,
            'action_url' => $this->action_url,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
