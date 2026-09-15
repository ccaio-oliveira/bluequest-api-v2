<?php

namespace App\Http\Controllers\Api;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Domain\InviteRules;
use App\Domain\InviteState;
use App\Domain\RecurrenceType;
use App\Domain\TaskRules;
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

        return response()->json($this->invitePayload($challenge, $request->user()->id));
    }

    public function rotate(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->creator_user_id === $request->user()->id, 403);

        DB::transaction(function () use ($challenge, $request) {
            $challenge->invites()->whereNull('revoked_at')->update(['revoked_at' => CarbonImmutable::now()]);
            $this->createInvite($challenge, $request->user()->id);
        });

        return response()->json($this->invitePayload($challenge, $request->user()->id), 201);
    }

    public function preview(Request $request, string $code)
    {
        $invite = Invite::with(['challenge.participants.user', 'challenge.tasks', 'createdBy'])->where('code', $code)->first();
        $challenge = $invite?->challenge;

        $challengeState = $challenge !== null ? ChallengeRules::state($challenge, CarbonImmutable::now()) : null;
        $membership = $this->membership($challenge, $request->user()->id);

        $state = InviteRules::state(
            $invite,
            $challengeState,
            $membership !== null && !$membership->trashed(),
            $membership?->trashed() ?? false,
        );

        return response()->json([
            'state' => $state->value,
            'challenge' => $state === InviteState::Invalid ? null : $this->challengePayload($challenge, $challengeState, $invite)
        ]);
    }

    public function accept(Request $request, string $code)
    {
        $invite = Invite::with('challenge')->where('code', $code)->first();
        $challenge = $invite?->challenge;
        $user = $request->user();

        $challengeState = $challenge !== null ? ChallengeRules::state($challenge, CarbonImmutable::now()) : null;
        $membership = $this->membership($challenge, $request->user()->id);

        $state = InviteRules::state(
            $invite,
            $challengeState,
            $membership !== null && !$membership->trashed(),
            $membership?->trashed() ?? false,
        );

        if ($state !== InviteState::Valid) {
            return response()->json([
                'error' => $state->value,
                'challenge_id' => $state === InviteState::AlreadyParticipant ? $challenge->id : null
            ], 422);
        }

        Participant::create([
            'user_id' => $user->id,
            'challenge_id' => $challenge->id,
            'invite_id' => $invite->id,
            'joined_at' => CarbonImmutable::now()
        ]);

        return response()->json(['challenge_id' => $challenge->id], 201);
    }

    public function update(Request $request, Challenge $challenge)
    {
        abort_unless($challenge->creator_user_id === $request->user()->id, 403);

        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        if (ChallengeRules::state($challenge, CarbonImmutable::now()) === ChallengeState::Closed) {
            return response()->json(['error' => 'challenge_closed'], 422);
        }

        $challenge->update(['invite_enabled' => $data['enabled']]);

        return response()->noContent();
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

    private function challengePayload(Challenge $challenge, ?ChallengeState $state, Invite $invite): array
    {
        $today = CarbonImmutable::now()->setTimezone($challenge->timezone)->toDateString();

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
            'invited_by' => $invite->createdBy?->name,
            'tasks_count' => $challenge->tasks->count(),
            'max_points_per_day' => $this->maxPointsPerDay($challenge, $today),
        ];
    }

    private function maxPointsPerDay(Challenge $challenge, string $today): int
    {
        $perWeekday = [];

        $tasks = $challenge->tasks->filter(fn ($task) => TaskRules::isCurrent($task, $today));

        foreach (range(1, 7) as $weekday) {
            $perWeekday[$weekday] = $tasks
            ->filter(fn ($task) => match ($task->recurrence_type) {
                RecurrenceType::Daily => true,
                RecurrenceType::Weekdays => in_array($weekday, $task->recurrence_weekdays ?? [], true),
                RecurrenceType::Once => false,
            })
            ->sum('points');
        }

        return (int) max($perWeekday);
    }

    private function membership(?Challenge $challenge, int $userId): ?Participant
    {
        if ($challenge === null) {
            return null;
        }

        return Participant::withTrashed()
        ->where('challenge_id', $challenge->id)
        ->where('user_id', $userId)
        ->first();
    }

    private function invitePayload(Challenge $challenge, int $userId): array
    {
        $isCreator = $challenge->creator_user_id === $userId;
        $invite = $challenge->invites()->whereNull('revoked_at')->latest('id')->first();

        if ($invite === null && $challenge->invite_enabled) {
            $invite = $this->createInvite($challenge, $userId);
        }

        $showsLink = $invite !== null && ($challenge->invite_enabled || $isCreator);

        return [
            'enabled' => $challenge->invite_enabled,
            'code' => $showsLink ? $invite->code : null,
            'link' => $showsLink ? 'bluequest://invite/' . $invite->code : null,
            'uses' => $invite?->participants()->withTrashed()->count() ?? 0,
        ];
    }
}
