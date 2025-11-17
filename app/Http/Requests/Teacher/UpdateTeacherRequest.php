<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use App\Models\User;
use App\Support\ClassroomOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof User
            ? $this->user()?->can('update', $teacher) ?? false
            : false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $teacher */
        $teacher = $this->route('teacher');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher?->id)],
            'password' => ['nullable', 'string', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:20'],
            'class_name' => ['sometimes', 'string', Rule::in(ClassroomOptions::classes())],
            'section' => ['sometimes', 'string', Rule::in(ClassroomOptions::sections())],
            'employee_code' => ['sometimes', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($teacher?->id)],
            'subject_specialization' => ['nullable', 'string', 'max:120'],
            'qualification' => ['nullable', 'string', 'max:120'],
            'date_of_joining' => ['nullable', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
