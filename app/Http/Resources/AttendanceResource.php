<?php

declare(strict_types=1);

namespace App\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read string $status
 * @property-read string|null $note
 * @property-read \Illuminate\Support\Carbon $attendance_date
 */
class AttendanceResource extends JsonResource
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
            'attendance_date' => $this->attendance_date?->toDateString(),
            'status' => $this->resolveStatusValue(),
            'note' => $this->note,
            'student' => [
                'id' => $this->student?->id,
                'name' => $this->student?->name,
                'student_id' => $this->student?->student_id,
                'class_name' => $this->student?->class_name,
                'section' => $this->student?->section,
            ],
            'recorded_by' => $this->recorded_by,
        ];
    }

    /**
     * Normalize the status value for serialization.
     */
    private function resolveStatusValue(): string
    {
        $status = $this->status;

        if ($status instanceof BackedEnum) {
            return $status->value;
        }

        return (string) $status;
    }
}
