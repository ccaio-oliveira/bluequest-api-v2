<?php

namespace App\Http\Controllers\Api;

use App\Domain\OccurrenceState;
use App\Domain\StreakRules;
use App\Http\Controllers\Controller;
use App\Models\Completion;
use App\Models\User;
use App\Services\HistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request, HistoryService $history)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        $user = $request->user();
        $now = CarbonImmutable::now();
        $month = CarbonImmutable::parse(($data['month'] ?? $now->format('Y-m')) . '-01');

        $byDate = $history->occurrencesByDate(
            $user,
            $month->toDateString(),
            $month->endOfMonth()->toDateString(),
            $now
        );

        $range = $history->range($user);
        $participantIds = $user->participations()->pluck('id');

        return response()->json([
            'month' => $month->format('Y-m'),
            'first_month' => $range === null ? $month->format('Y-m') : substr($range['first'], 0, 7),
            'last_month' => $range === null ? $month->format('Y-m') : substr($range['last'], 0, 7),
            'totals' => [
                'completions' => Completion::whereIn('participant_id', $participantIds)->count(),
                'points' => (int) Completion::whereIn('participant_id', $participantIds)->sum('points_awarded'),
                'streak_days' => $this->streak($user, $history, $range, $now),
            ],
            'days' => array_map(
                fn ($date) => $this->dayPayload($date, $byDate[$date]),
                array_keys($byDate),
            ),
        ]);
    }

    private function streak(User $user, HistoryService $history, ?array $range, CarbonImmutable $now): int
    {
        if ($range === null) {
            return 0;
        }

        $today = $now->setTimezone($range['timezone'])->toDateString();

        $byDate = $history->occurrencesByDate($user, $range['first'], min($range['last'], $today), $now);

        return StreakRules::current($byDate, CarbonImmutable::parse($today));
    }

    private function dayPayload(string $date, array $occurrences): array
    {
        $completed = array_filter($occurrences, fn ($o) => $o->state === OccurrenceState::Completed);
        $expired = array_filter($occurrences, fn ($o) => $o->state === OccurrenceState::Expired);

        return [
            'date' => $date,
            'total' => count($occurrences),
            'completed' => count($completed),
            'expired' => count($expired),
            'points' => array_sum(array_map(fn ($o) => $o->completion?->points_awarded ?? 0, $occurrences)),
            'has_photo' => count(array_filter($occurrences, fn ($o) => $o->completion?->photo_url !== null)) > 0,
            'tasks' => array_map(fn ($o) => [
                'name' => $o->task->name,
                'challenge_name' => $o->task->challenge->name,
                'points' => $o->task->points,
                'state' => $o->state->value,
                'deadline_time' => substr($o->task->deadline_time, 0, 5),
                'has_photo' => $o->completion?->photo_url !== null,
            ], array_values($occurrences)),
        ];
    }
}
