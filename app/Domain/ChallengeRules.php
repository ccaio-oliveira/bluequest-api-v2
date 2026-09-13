<?php

namespace App\Domain;

use App\Models\Challenge;
use Carbon\CarbonImmutable;

final class ChallengeRules
{
    public static function state(Challenge $challenge, CarbonImmutable $now): ChallengeState
    {
        $start = self::midnight($challenge->start_date, $challenge->timezone);
        $dayAfterEnd = self::midnight($challenge->end_date, $challenge->timezone)->addDay();

        if($now < $start) {
            return ChallengeState::Future;
        }

        if ($now < $dayAfterEnd) {
            return ChallengeState::InProgress;
        }

        return ChallengeState::Closed;
    }

    public static function totalDays(Challenge $challenge): int
    {
        $start = self::midnight($challenge->start_date, $challenge->timezone);
        $end = self::midnight($challenge->end_date, $challenge->timezone);

        return (int) round($start->diffInDays($end)) + 1;
    }

    public static function currentDay(Challenge $challenge, CarbonImmutable $now): int
    {
        $start = self::midnight($challenge->start_date, $challenge->timezone);
        $today = $now->setTimezone($challenge->timezone)->startOfDay();

        $elapsed = (int) round($start->diffInDays($today));

        return max(1, min($elapsed + 1, self::totalDays($challenge)));
    }

    public static function validateUpdate(
        Challenge $challenge,
        string $newStart,
        string $newEnd,
        CarbonImmutable $now
    ): void
    {
        $state = self::state($challenge, $now);

        if ($state === ChallengeState::Closed) {
            throw new ChallengeException('challenge_closed');
        }

        $today = $now->setTimezone($challenge->timezone)->toDateString();
        $startChanged = $newStart !== $challenge->start_date->toDateString();

        if ($startChanged && $state !== ChallengeState::Future) {
            throw new ChallengeException('start_locked');
        }

        if ($startChanged && $newStart < $today) {
            throw new ChallengeException('start_in_past');
        }

        if ($newEnd < $today) {
            throw new ChallengeException('end_in_past');
        }
    }

    private static function midnight(CarbonImmutable $date, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::create(
            $date->year,
            $date->month,
            $date->day,
            0,
            0,
            0,
            $timezone
        );
    }
}
