<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Domain\InviteRules;
use App\Domain\InviteState;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Invite;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function show(Request $request, Challenge $challenge)
    {
        $this->ensureParticipant($request, $challenge);

        $invite = $challenge->invites()->whereNull('revoked_at')->latest('id')->first() ?? $this->createInvite($challenge, $request->user()->id);

        return response()->json($this->linkPayload($invite));
    }

    public function rotate(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->creator_user_id === $request->user()->id, 403);

        $invite = DB::transaction(function () use ($challenge, $request) {
            $challenge->invites()->whereNull('revoked_at')->update(['revoked_at' => CarbonImmutable::now()]);

            return $this->createInvite($challenge, $request->user()->id);
        });

        return response()->json($this->linkPayload($invite), 201);
    }

    public function preview(Request $request, string $code)
    {
        $invite = Invite::with('challenge.participants.user')->where('code', $code)->first();
        $challenge = $invite?->challenge;

        $isParticipant = $challenge != null && Participant::query()
        ->where('challenge_id', $challenge->id)
        ->where('user_id', $request->user()->id)
        ->exists();

        $challengeState = $challenge !== null ? ChallengeRules::state($challenge, CarbonImmutable::now()) : null;

        $state = InviteRules::state($invite, $challengeState, $isParticipant);

        return response()->json([
            'state' => $state->value,
            'challenge' => $state === InviteState::Invalid ? null : $this->challengePayload($challenge, $challengeState)
        ]);
    }

    public function accept(Request $request, string $code)
    {
        $invite = Invite::with('challenge')->where('code', $code)->first();
        $challenge = $invite?->challenge;
        $user = $request->user();

        $isParticipant = $challenge != null && Participant::query()
        ->where('challenge_id', $challenge->id)
        ->where('user_id', $user->id)
        ->exists();

        $challengeState = $challenge !== null ? ChallengeRules::state($challenge, CarbonImmutable::now()) : null;

        $state = InviteRules::state($invite, $challengeState, $isParticipant);

        if ($state !== InviteState::Valid) {
            return response()->json([
                'error' => $state->value,
                'challenge_id' => $state === InviteState::AlreadyParticipant ? $challenge->id : null
            ], 422);
        }

        DB::transaction(function () use ($challenge, $user, $invite) {
            Participant::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'joined_at' => CarbonImmutable::now()
            ]);

            if ($invite->used_at === null) {
                $invite->update([
                    'used_by_user_id' => $user->id,
                    'used_at' => CarbonImmutable::now()
                ]);
            }
        });

        return response()->json(['challenge_id' => $challenge->id], 201);
    }

    private function ensureParticipant(Request $request, Challenge $challenge): void
    {
        abort_unless(
            Participant::where('challenge_id', $challenge->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            403
        );
    }

    private function createInvite(Challenge $challenge, int $userId): Invite
    {
        $slug = Str::slug(Str::limit($challenge->name, 20, ''));

        do {
            $code = trim($slug . '-' . Str::lower(Str::random(6)), '-');
        } while (Invite::where('code', $code)->exists());

        return $challenge->invites()->create([
            'created_by_user_id' => $userId,
            'code' => $code
        ]);
    }

    private function linkPayload(Invite $invite): array
    {
        return [
            'code' => $invite->code,
            'link' => 'bluequest://invite/' . $invite->code,
        ];
    }

    private function challengePayload(Challenge $challenge, ?ChallengeState $state): array
    {
        return [
            'id' => $challenge->id,
            'name' => $challenge->name,
            'description' => $challenge->description,
            'start_date' => $challenge->start_date->toDateString(),
            'end_date' => $challenge->end_date->toDateString(),
            'state' => $state?->value,
            'total_days' => ChallengeRules::totalDays($challenge),
            'participants_count' => $challenge->participants->count(),
            'participants' => $challenge->participants->take(4)
            ->map(fn ($participant) => ['name' => $participant->user->name])
            ->values(),
        ];
    }
}
