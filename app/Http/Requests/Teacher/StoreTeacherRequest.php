<?php

declare(strict_types=1);

namespace App\Http\Requests\Teacher;

use App\Models\User;
use App\Support\ClassroomOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:20'],
            'class_name' => ['required', 'string', Rule::in(ClassroomOptions::classes())],
            'section' => ['required', 'string', Rule::in(ClassroomOptions::sections())],
            'employee_code' => ['required', 'string', 'max:50', 'unique:users,employee_code'],
            'subject_specialization' => ['nullable', 'string', 'max:120'],
            'qualification' => ['nullable', 'string', 'max:120'],
            'date_of_joining' => ['nullable', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
