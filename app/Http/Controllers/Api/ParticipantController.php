<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeException;
use App\Domain\ChallengeRules;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function destroy(Request $request, Challenge $challenge, User $user)
    {
        abort_unless($challenge->creator_user_id === $request->user()->id, 403);

        try {
            ChallengeRules::validateParticipantRemoval($challenge, $user->id, CarbonImmutable::now());
        } catch (ChallengeException $e) {
            return response()->json(['error' => $e->reason], 422);
        }

        $challenge->participants()
        ->where('user_id', $user->id)
        ->firstOrFail()
        ->delete();

        return response()->noContent();
    }
}
