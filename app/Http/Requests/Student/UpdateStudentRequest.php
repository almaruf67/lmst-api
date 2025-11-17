<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Models\User;
use App\Support\ClassroomOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            ? $this->user()?->can('update', $student) ?? false
            : false;
    }

    /**
     * Prepare for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user instanceof User && $user->isTeacher()) {
            $this->merge([
                'class_name' => $user->class_name,
                'section' => $user->section,
                'primary_teacher_id' => $user->id,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Student $student */
        $student = $this->route('student');
        $user = $this->user();

        $classRules = ['sometimes', 'string'];
        $sectionRules = ['nullable', 'string'];

        if (! $user instanceof User || ! $user->isTeacher()) {
            $classRules[] = Rule::in(ClassroomOptions::classes());
            $sectionRules[] = Rule::in(ClassroomOptions::sections());
        }

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'student_id' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('students', 'student_id')->ignore($student?->id),
            ],
            'class_name' => $classRules,
            'section' => $sectionRules,
            'notes' => ['nullable', 'string'],
            'primary_teacher_id' => ['nullable', 'exists:users,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
