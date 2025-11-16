<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Student */
class StudentResource extends JsonResource
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
            'name' => $this->name,
            'student_id' => $this->student_id,
            'slug' => $this->slug,
            'class_name' => $this->class_name,
            'section' => $this->section,
            'photo_url' => $this->photo_url,
            'notes' => $this->notes,
            'primary_teacher' => $this->whenLoaded('primaryTeacher', function () {
                return [
                    'id' => $this->primaryTeacher?->id,
                    'name' => $this->primaryTeacher?->name,
                ];
            }),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
