<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'type' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'in:high,medium,low'],
            'status' => ['nullable', 'string', 'in:read,unread'],
            'audience' => ['nullable', 'string', 'in:admin,teacher'],
        ];
    }
}
