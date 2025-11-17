<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdmin() || $user?->isTeacher();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'class_name' => ['nullable', 'string', 'max:120'],
            'section' => ['nullable', 'string', 'max:50'],
            'format' => ['nullable', 'string', 'in:excel,csv,pdf,json'],
        ];
    }
}
