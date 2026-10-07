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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string']
        ]);

        if (strcasecmp($data['email'], $user->email) !== 0) {
            if ($user->password === null) {
                return response()->json(['error' => 'password_required_for_email'], 422);
            }

            if (!Hash::check($data['current_password'] ?? '', $user->password)) {
                return response()->json(['error' => 'current_password_invalid'], 422);
            }
        }

        $user->update(['name' => $data['name'], 'email' => $data['email']]);

        return response()->json($user->profilePayload());
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        if ($user->password !== null && !Hash::check($data['current_password'] ?? '', $user->password)) {
            return response()->json(['error' => 'current_password_invalid'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json($user->profilePayload());
    }
}
