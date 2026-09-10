<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Domain\RecurrenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChallengeRequest;
use App\Models\Challenge;
use App\Models\Participant;
use App\Models\User;
use App\Services\RankingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChallengeController extends Controller
{
    public function index(Request $request, RankingService $ranking)
    {
        $user = $request->user();
        $now = CarbonImmutable::now();

        $challenges = Challenge::query()
        ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
        ->with('participants.user')
        ->orderBy('start_date')
        ->get();

        return response()->json([
            'challenges' => $challenges->map(fn (Challenge $challenge) => $this->present($challenge, $ranking, $user, $now)),
        ]);
    }

    public function store(StoreChallengeRequest $request, RankingService $ranking)
    {
        $user = $request->user();
        $data = $request->validated();

        $challenge = DB::transaction(function () use ($data, $user) {
            $challenge = Challenge::create([
                'creator_user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'timezone' => $data['timezone'] ?? 'America/Sao_Paulo',
            ]);

            Participant::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'joined_at' => CarbonImmutable::now(),
            ]);

            foreach ($data['tasks'] ?? [] as $task) {
                $type = RecurrenceType::from($task['recurrence_type']);

                $challenge->tasks()->create([
                    'name' => $task['name'],
                    'description' => $task['description'] ?? null,
                    'points' => $task['points'],
                    'recurrence_type' => $type,
                    'recurrence_weekdays' => $type === RecurrenceType::Weekdays ? array_values(array_unique($task['recurrence_weekdays'])) : null,
                    'recurrence_date' => $type === RecurrenceType::Once ? $task['recurrence_date'] : null,
                    'deadline_time' => $task['deadline_time'],
                    'photo_requirement' => $task['photo_requirement'],
                ]);
            }

            return $challenge;
        });

        $challenge->load('participants.user');

        return response()->json($this->present($challenge, $ranking, $user, CarbonImmutable::now()), 201);
    }

    private function present(Challenge $challenge, RankingService $ranking, User $user, CarbonImmutable $now): array
    {
        $ranked = $ranking->rank($challenge);
        $mine = $ranked->firstWhere('user_id', $user->id);

        return [
            'id' => $challenge->id,
            'name' => $challenge->name,
            'description' => $challenge->description,
            'start_date' => $challenge->start_date->toDateString(),
            'end_date' => $challenge->end_date->toDateString(),
            'state' => ChallengeRules::state($challenge, $now)->value,
            'current_day' => ChallengeRules::currentDay($challenge, $now),
            'total_days' => ChallengeRules::totalDays($challenge),
            'my_points' => $mine?->points_total ?? 0,
            'my_rank' => $mine?->rank_position,
            'participants_count' => $challenge->participants->count(),
            'participants' => $challenge->participants->take(4)->map(fn ($participant) => [
                'id' => $participant->user->id,
                'name' => $participant->user->name,
                'avatar_url' => $participant->user->avatar_url,
            ])->values(),
        ];
    }
}
