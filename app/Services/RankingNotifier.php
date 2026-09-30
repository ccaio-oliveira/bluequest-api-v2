<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Participant;
use App\Notifications\RankingOvertaken;
use Illuminate\Support\Collection;

final class RankingNotifier
{
    /** @return Collection<int, int> pontos por participant_id */
    public function snapshot(Challenge $challenge): Collection
    {
        return $challenge->participants()
        ->withSum('completions as points', 'points_awarded')
        ->get()
        ->mapWithKeys(fn ($participant) => [$participant->id => (int) $participant->points]);
    }

    /** @param Collection<int, int> $before */
    public function notifyOvertaken(Participant $mover, Collection $before, int $gained): void
    {
        $from = $before->get($mover->id, 0);
        $to = $from + $gained;

        $overtaken = $before->filter(
            fn ($points, $participantId) => $participantId !== $mover->id && $from <= $points && $to > $points
        );

        if ($overtaken->isEmpty()) {
            return;
        }

        $after = $before->replace([$mover->id => $to]);

        Participant::with('user')
        ->whereIn('id', $overtaken->keys())
        ->get()
        ->each(function (Participant $participant) use ($mover, $after) {
            $points = $after[$participant->id];
            $position = 1 + $after->filter(fn ($other) => $other > $points)->count();

            $participant->user->notify(new RankingOvertaken($mover->challenge, $mover->user, $position));
        });
    }
}
