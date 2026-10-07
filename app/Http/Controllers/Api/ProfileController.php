<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Completion;
use App\Services\RankingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function stats(Request $request, RankingService $ranking)
    {
        $user = $request->user();
        $now = CarbonImmutable::now();
        $participations = $user->participations()->with('challenge')->get();

        $wins = $participations
        ->map(fn ($participant) => $participant->challenge)
        ->filter(fn (Challenge $challenge) => ChallengeRules::state($challenge, $now) === ChallengeState::Closed)
        ->filter(function (Challenge $challenge) use ($ranking, $user) {
            $ranked = $ranking->rank($challenge);
            $mine = $ranked->firstWhere('user_id', $user->id);

            return $ranked->count() >= 2 && $mine?->rank_position === 1 && $mine->points_total > 0;
        })
        ->count();

        return response()->json([
            'challenges' => $participations->count(),
            'points' => (int) Completion::whereIn('participant_id', $participations->pluck('id'))->sum('points_awarded'),
            'wins' => $wins
        ]);
    }
}
