<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

class ClassRosterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Student::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAdmin = $this->user()?->isAdmin() ?? false;

        $classRules = ['string', 'max:120'];
        array_unshift($classRules, $isAdmin ? 'required' : 'nullable');

        return [
            'class_name' => $classRules,
            'section' => ['nullable', 'string', 'max:50'],
        ];
    }
}
