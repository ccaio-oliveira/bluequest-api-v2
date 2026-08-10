<?php

namespace App\Domain;

use App\Models\Task;
use Carbon\CarbonImmutable;

final class CompletionRules
{
    public static function validate(
        Task $task,
        CarbonImmutable $occurrenceDate,
        bool $isParticipant,
        bool $isAlreadyCompleted,
        CarbonImmutable $now,
    ): void {
        if (!$isParticipant) {
            throw new CompletionException('user_not_participant');
        }

        $challenge = $task->challenge;

        if ($occurrenceDate->lt($challenge->start_date) || $occurrenceDate->gt($challenge->end_date)) {
            throw new CompletionException('outside_challenge_period');
        }

        if (!$task->recurrence()->occursOn($occurrenceDate)) {
            throw new CompletionException('occurrence_does_not_exist');
        }

        $state = OccurrenceRules::stateFor($task, $occurrenceDate, $isAlreadyCompleted, $now);

        if ($state !== OccurrenceState::Available) {
            throw new CompletionException('occurrence_not_available', $state);
        }
    }
}
