<?php

namespace App\Domain;

use Carbon\CarbonImmutable;

final class StreakRules
{
    /** @param array<string, Occurrence[]> $byDate */
    public static function current(array $byDate, CarbonImmutable $today): int
    {
        $dates = array_keys($byDate);
        rsort($dates);

        $streak = 0;

        foreach ($dates as $date) {
            $occurrences = $byDate[$date];
            $completed = array_filter($occurrences, fn ($o) => $o->state === OccurrenceState::Completed);

            if (count($completed) === count($occurrences)) {
                $streak++;
                continue;
            }

            $isToday = $date === $today->toDateString();
            $stillOpen = count(array_filter($occurrences, fn ($o) => $o->state === OccurrenceState::Available)) > 0;

            if ($isToday && $stillOpen) {
                continue;
            }

            break;
        }

        return $streak;
    }
}
