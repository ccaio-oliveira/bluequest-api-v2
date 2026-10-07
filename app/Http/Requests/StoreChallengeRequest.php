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
            'tasks.*.recurrence_dates' => ['required_if:tasks.*.recurrence_type,dates', 'nullable', 'array', 'min:1', 'max:60'],
            'tasks.*.recurrence_dates.*' => ['date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:end_date'],
            'tasks.*.recurrence_times_per_week' => ['required_if:tasks.*.recurrence_type,weekly', 'nullable', 'integer', 'between:1,6'],
            'tasks.*.deadline_time' => ['required', 'date_format:H:i'],
            'tasks.*.photo_requirement' => ['required', Rule::in(['none', 'required'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'início',
            'end_date' => 'término',
            'tasks.*.name' => 'nome da tarefa',
            'tasks.*.points' => 'pontos',
            'tasks.*.recurrence_weekdays' => 'dias da semana',
            'tasks.*.recurrence_dates' => 'datas',
            'tasks.*.recurrence_dates.*' => 'data',
            'tasks.*.recurrence_times_per_week' => 'vezes por semana',
            'tasks.*.deadline_time' => 'prazo',
        ];
    }
}
