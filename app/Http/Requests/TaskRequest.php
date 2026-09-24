<?php

namespace App\Http\Requests;

use App\Domain\ChallengeRules;
use App\Domain\RecurrenceType;
use App\Domain\TaskRules;
use App\Models\Challenge;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->challenge()->creator_user_id === $this->user()->id;
    }

    public function rules(): array
    {
        $challenge = $this->challenge();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'recurrence_type' => ['required', Rule::enum(RecurrenceType::class)],
            'recurrence_weekdays' => ['required_if:recurrence_type,weekdays', 'nullable', 'array', 'min:1'],
            'recurrence_weekdays.*' => ['integer', 'between:1,7'],
            'recurrence_dates' => ['required_if:recurrence_type,dates', 'nullable', 'array', 'min:1', 'max:60'],
            'recurrence_dates.*' => [
                'date_format:Y-m-d',
                'after_or_equal:' . $this->firstPossibleDate($challenge),
                'before_or_equal:' . $challenge->end_date->toDateString(),
            ],
            'deadline_time' => ['required', 'date_format:H:i'],
            'photo_requirement' => ['required', Rule::in(['none', 'required'])],
        ];
    }

    private function challenge(): Challenge
    {
        return $this->route('challenge') ?? $this->route('task')->challenge;
    }

    private function firstPossibleDate(Challenge $challenge): string
    {
        $now = CarbonImmutable::now();
        $today = $now->setTimezone($challenge->timezone)->toDateString();

        return TaskRules::activeFrom(ChallengeRules::state($challenge, $now), $today) ?? $challenge->start_date->toDateString();
    }
}
