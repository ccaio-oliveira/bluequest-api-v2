<?php

namespace App\Domain;

use App\Models\Task;
use Carbon\CarbonImmutable;

final class OccurrenceRules
{
    public static function deadlineFor(Task $task, CarbonImmutable $date): CarbonImmutable
    {
        [$hour, $minute] = explode(':', $task->deadline_time);

        return CarbonImmutable::create(
            $date->year,
            $date->month,
            $date->day,
            (int) $hour,
            (int) $minute,
            0,
            $task->challenge->timezone,
        );
    }

    public static function stateFor(
        Task $task,
        CarbonImmutable $date,
        bool $isCompleted,
        CarbonImmutable $now,
    ): OccurrenceState {
        if ($isCompleted) {
            return OccurrenceState::Completed;
        }

        $startOfDay = CarbonImmutable::create(
            $date->year,
            $date->month,
            $date->day,
            0,
            0,
            0,
            $task->challenge->timezone
        );
        $deadline = self::deadlineFor($task, $date);

        if ($now < $startOfDay) {
            return OccurrenceState::Future;
        }

        if ($now <= $deadline) {
            return OccurrenceState::Available;
        }

        return OccurrenceState::Expired;
    }
}
