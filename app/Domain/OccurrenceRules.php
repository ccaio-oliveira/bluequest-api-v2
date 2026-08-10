<?php

namespace App\Domain;

use App\Models\Task;
use Carbon\CarbonImmutable;

final class OccurrenceRules
{
    public static function deadlineFor(Task $task, CarbonImmutable $date): CarbonImmutable
    {
        [$hour, $minute] = explode(':', $task->deadline_time);

        return $date->setTimezone($task->challenge->timezone)->setTime((int) $hour, (int) $minute, 0);
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

        $startOfDay = $date->setTimezone($task->challenge->timezone)->startOfDay();
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
