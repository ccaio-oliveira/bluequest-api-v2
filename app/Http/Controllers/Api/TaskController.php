<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Domain\RecurrenceType;
use App\Domain\TaskRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Challenge;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class TaskController extends Controller
{
    public function store(TaskRequest $request, Challenge $challenge)
    {
        $now = CarbonImmutable::now();
        $state = ChallengeRules::state($challenge, $now);

        if ($state === ChallengeState::Closed) {
            return response()->json(['error' => 'challenge_closed'], 422);
        }

        $today = $now->setTimezone($challenge->timezone)->toDateString();

        $task = $challenge->tasks()->create([
            ...$this->attributes($request->validated()),
            'active_from' => $state === ChallengeState::Future ? null : CarbonImmutable::parse($today)->addDay()->toDateString(),
        ]);

        return response()->json(['id' => $task->id], 201);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $challenge = $task->challenge;
        $now = CarbonImmutable::now();
        $state = ChallengeRules::state($challenge, $now);

        if ($state === ChallengeState::Closed) {
            return response()->json(['error' => 'challenge_closed'], 422);
        }

        $today = $now->setTimezone($challenge->timezone)->toDateString();
        $attributes = $this->attributes($request->validated());

        if (!TaskRules::hasGeneratedOccurrences($task, $state, $today)) {
            $task->update($attributes);

            return response()->noContent();
        }

        DB::transaction(function () use ($task, $challenge, $attributes, $today) {
            $task->update(['active_until' => $today]);

            $challenge->tasks()->create([
                ...$attributes,
                'active_from' => CarbonImmutable::parse($today)->addDay()->toDateString(),
            ]);
        });

        return response()->noContent();
    }

    public function destroy(Request $request, Task $task)
    {
        $challenge = $task->challenge;

        abort_unless($challenge->creator_user_id === $request->user()->id, 403);

        $now = CarbonImmutable::now();
        $state = ChallengeRules::state($challenge, $now);

        if ($state === ChallengeState::Closed) {
            return response()->json(['error' => 'challenge_closed'], 422);
        }

        $today = $now->setTimezone($challenge->timezone)->toDateString();

        if (TaskRules::hasGeneratedOccurrences($task, $state, $today)) {
            $task->update(['active_until' => $today]);
        } else {
            $task->delete();
        }

        return response()->noContent();
    }

    private function attributes(array $data): array
    {
        $type = RecurrenceType::from($data['recurrence_type']);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'points' => $data['points'],
            'recurrence_type' => $type,
            'recurrence_weekdays' => $type === RecurrenceType::Weekdays ? array_values(array_unique($data['recurrence_weekdays'])) : null,
            'recurrence_date' => $type === RecurrenceType::Once ? $data['recurrence_date'] : null,
            'deadline_time' => $data['deadline_time'],
            'photo_requirement' => $data['photo_requirement'],
        ];
    }
}
