<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Services\RankingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

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
            'challenges' => $challenges->map(function (Challenge $challenge) use ($ranking, $user, $now) {
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
            })
        ]);
    }
}
