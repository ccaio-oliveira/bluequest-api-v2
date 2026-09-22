<?php

namespace App\Http\Requests;

use App\Domain\RecurrenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $challenge = $this->route('challenge') ?? $this->route('task')->challenge;

        return $challenge->creator_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'recurrence_type' => ['required', Rule::enum(RecurrenceType::class)],
            'recurrence_weekdays' => ['required_if:recurrence_type,weekdays', 'nullable', 'array', 'min:1'],
            'recurrence_weekdays.*' => ['integer', 'between:1,7'],
            'recurrence_date' => ['required_if:recurrence_type,once', 'nullable', 'date_format:Y-m-d'],
            'deadline_time' => ['required', 'date_format:H:i'],
            'photo_requirement' => ['required', Rule::in(['none', 'required'])],
        ];
    }
}
