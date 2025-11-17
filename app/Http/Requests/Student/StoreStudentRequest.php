<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Models\User;
use App\Support\ClassroomOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /**
     * Prepare the data for validation.
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
        $user = $this->user();

        $classRules = ['required', 'string'];
        $sectionRules = ['nullable', 'string'];

        if (! $user instanceof User || ! $user->isTeacher()) {
            $classRules[] = Rule::in(ClassroomOptions::classes());
            $sectionRules[] = Rule::in(ClassroomOptions::sections());
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id'],
            'class_name' => $classRules,
            'section' => $sectionRules,
            'notes' => ['nullable', 'string'],
            'primary_teacher_id' => ['nullable', 'exists:users,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
