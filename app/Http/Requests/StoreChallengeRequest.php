<?php

namespace App\Http\Requests;

use App\Domain\RecurrenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChallengeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'timezone' => ['nullable', 'timezone'],
            'tasks' => ['nullable', 'array', 'max:20'],
            'tasks.*.name' => ['required', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string', 'max:1000'],
            'tasks.*.points' => ['required', 'integer', 'min:1', 'max:1000'],
            'tasks.*.recurrence_type' => ['required', Rule::enum(RecurrenceType::class)],
            'tasks.*.recurrence_weekdays' => ['required_if:tasks.*.recurrence_type,weekdays', 'nullable', 'array', 'min:1'],
            'tasks.*.recurrence_weekdays.*' => ['integer', 'between:1,7'],
            'tasks.*.recurrence_date' => ['required_if:tasks.*.recurrence_type,once', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:end_date'],
            'tasks.*.deadline_time' => ['required', 'date_format:H:i'],
            'tasks.*.photo_requirement' => ['required', Rule::in(['none', 'optional', 'required'])],
        ];
    }
}
