<?php

namespace App\Http\Controllers\Api;

use App\Domain\OccurrenceRules;
use App\Http\Controllers\Controller;
use App\Services\OccurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class TodayController extends Controller
{
    public function index(Request $request, OccurrenceService $service)
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $now = CarbonImmutable::now();
        $date = $request->filled('date') ? CarbonImmutable::parse($request->string('date')) : $now;
        $occurrences = $service->forUserOnDate($request->user(), $date, $now);

        return response()->json([
            'date' => $date->toDateString(),
            'occurrences' => array_map(fn ($occurrence) => [
                'task_id' => $occurrence->task->id,
                'challenge_id' => $occurrence->task->challenge_id,
                'challenge_name' => $occurrence->task->challenge->name,
                'name' => $occurrence->task->name,
                'description' => $occurrence->task->description,
                'points' => $occurrence->task->points,
                'photo_requirement' => $occurrence->task->photo_requirement,
                'deadline_at' => OccurrenceRules::deadlineFor($occurrence->task, $occurrence->date)->toIso8601String(),
                'occurrence_date' => $occurrence->date->toDateString(),
                'state' => $occurrence->state->value,
                'points_awarded' => $occurrence->completion?->points_awarded,
            ], $occurrences),
        ]);
    }
}
