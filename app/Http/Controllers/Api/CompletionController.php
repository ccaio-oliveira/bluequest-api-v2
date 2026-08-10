<?php

namespace App\Http\Controllers\Api;

use App\Domain\CompletionException;
use App\Domain\CompletionRules;
use App\Http\Controllers\Controller;
use App\Models\Completion;
use App\Models\Participant;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'occurrence_date' => ['required', 'date_format:Y-m-d'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $task = Task::with('challenge')->findOrFail($data['task_id']);
        $occurrenceDate = CarbonImmutable::parse($data['occurrence_date']);

        $participant = Participant::where('user_id', $request->user()->id)
        ->where('challenge_id', $task->challenge_id)
        ->first();

        $alreadyCompleted = $participant !== null && Completion::query()
        ->where('participant_id', $participant->id)
        ->where('task_id', $task->id)
        ->whereDate('occurrence_date', $occurrenceDate)
        ->exists();

        try {
            CompletionRules::validate(
                task: $task,
                occurrenceDate: $occurrenceDate,
                isParticipant: $participant !== null,
                isAlreadyCompleted: $alreadyCompleted,
                now: CarbonImmutable::now(),
            );
        } catch (CompletionException $e) {
            return response()->json([
                'error' => $e->reason,
                'state' => $e->state?->value,
            ], 422);
        }

        $completion = Completion::create([
            'participant_id' => $participant->id,
            'task_id' => $task->id,
            'occurrence_date' => $occurrenceDate,
            'completed_at' => CarbonImmutable::now(),
            'points_awarded' => $task->points,
            'photo_url' => $data['photo_url'] ?? null,
        ]);

        return response()->json([
            'id' => $completion->id,
            'task_id' => $completion->task_id,
            'occurrence_date' => $completion->occurrence_date->toDateString(),
            'points_awarded' => $completion->points_awarded,
            'state' => 'completed',
        ], 201);
    }
}
