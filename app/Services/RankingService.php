<?php

namespace App\Services;

use App\Models\Challenge;
use Illuminate\Support\Collection;

final class RankingService
{
    public function rank(Challenge $challenge): Collection
    {
        $participants = $challenge->participants()
        ->with('user')
        ->withSum('completions as points_total', 'points_awarded')
        ->get()
        ->each(fn ($participant) => $participant->points_total = (int) ($participant->points_total ?? 0))
        ->sortByDesc('points_total')
        ->values();

        $previousPoints = null;
        $previousPosition = 0;

        return $participants->map(function ($participant, $index) use (&$previousPoints, &$previousPosition) {
            $points = $participant->points_total;
            $position = ($previousPoints === $points) ? $previousPosition : $index + 1;

            $previousPoints = $points;
            $previousPosition = $position;

            $participant->rank_position = $position;

            return $participant;
        });
    }
}
