<?php

namespace App\Domain;

use App\Models\Task;
use Carbon\CarbonImmutable;

final class TaskRules
{
    public static function isActiveOn(Task $task, CarbonImmutable $date): bool
    {
        $day = $date->toDateString();

        if ($task->active_from !== null && $day < $task->active_from->toDateString()) {
            return false;
        }

        if ($task->active_until !== null && $day > $task->active_until->toDateString()) {
            return false;
        }

        return true;
    }

    public static function isCurrent(Task $task, string $today): bool
    {
        return $task->active_until === null || $task->active_until->toDateString() > $today;
    }

    public static function hasGeneratedOccurrences(
        Task $task,
        ChallengeState $state,
        string $today
    ): bool {
        if ($state === ChallengeState::Future) {
            return false;
        }

        return $task->active_from === null || $task->active_from->toDateString() <= $today;
    }

    public static function activeFrom(ChallengeState $state, string $today): ?string
    {
        return $state === ChallengeState::Future ? null : CarbonImmutable::parse($today)->addDay()->toDateString();
    }

    /**
     * @param string[] $dates
     * @return string[]
     */
    public static function sortedDates(array $dates): array
    {
        $dates = array_unique($dates);
        sort($dates);

        return array_values($dates);
    }
}
