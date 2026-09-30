<?php

namespace App\Services;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Domain\Occurrence;
use App\Domain\OccurrenceRules;
use App\Domain\OccurrenceState;
use App\Domain\RecurrenceType;
use App\Domain\TaskRules;
use App\Domain\WeeklyGoal;
use App\Models\Completion;
use App\Models\Participant;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

final class OccurrenceService
{
    /** @return Occurrence[] */
    public function forParticipantOnDate(
        Participant $participant,
        CarbonImmutable $date,
        CarbonImmutable $now
    ): array {
        $challenge = $participant->challenge;

        if ($date->lt($challenge->start_date) || $date->gt($challenge->end_date)) {
            return [];
        }

        return $this->forParticipantInRange($participant, $date, $date, $now);
    }

    /** @return Occurrence[] */
    public function forUserOnDate(User $user, ?CarbonImmutable $date, CarbonImmutable $now): array
    {
        $participants = $user->participations()
        ->with(['challenge.tasks'])
        ->get();

        $occurrences = [];

        foreach ($participants as $participant) {
            $challenge = $participant->challenge;

            if (ChallengeRules::state($challenge, $now) === ChallengeState::Closed) {
                continue;
            }

            $day = $date ?? CarbonImmutable::parse($now->setTimezone($challenge->timezone)->toDateString());

            $occurrences = [
                ...$occurrences,
                ...$this->forParticipantOnDate($participant, $day, $now),
            ];
        }

        return $occurrences;
    }

    /** @return Occurrence[] */
    public function forParticipantInRange(
        Participant $participant,
        CarbonImmutable $from,
        CarbonImmutable $to,
        CarbonImmutable $now,
    ): array {
        $tasks = $participant->challenge->tasks;

        $completions = $participant->completions()
        ->whereBetween('occurrence_date', [$from->startOfWeek(CarbonImmutable::MONDAY)->toDateString(), $to->endOfWeek(CarbonImmutable::SUNDAY)->toDateString()])
        ->get();

        $byTaskAndDate = $completions->keyBy(fn ($completion) => $completion->task_id . '|' . $completion->occurrence_date->toDateString());

        $completedDays = $completions->groupBy('task_id')
        ->map(fn ($group) => $group->map(fn ($completion) => $completion->occurrence_date->toDateString())->all());

        $occurrences = [];
        $date = $from;

        while ($date <= $to) {
            foreach ($tasks as $task) {
                if (!TaskRules::isActiveOn($task, $date) || !$task->recurrence()->occursOn($date)) {
                    continue;
                }

                $completion = $byTaskAndDate->get($task->id . '|' . $date->toDateString());

                if ($task->recurrence_type === RecurrenceType::Weekly) {
                    array_push($occurrences, ...$this->weeklyOccurrences($task, $date, $completion, $completedDays->get($task->id, []), $now));
                    continue;
                }

                $occurrences[] = new Occurrence(
                    task: $task,
                    date: $date,
                    state: OccurrenceRules::stateFor($task, $date, $completion !== null, $now),
                    completion: $completion,
                );
            }

            $date = $date->addDay();
        }

        return $occurrences;
    }

    /**
     * @param string[] $completedDays
     * @return Occurrence[]
     */
    private function weeklyOccurrences(
        Task $task,
        CarbonImmutable $date,
        ?Completion $completion,
        array $completedDays,
        CarbonImmutable $now,
    ): array {
        $goal = WeeklyGoal::for($task, $date, $completedDays);
        $state = OccurrenceRules::stateFor($task, $date, false, $now);
        $occurrences = [];

        if ($completion !== null) {
            $occurrences[] = new Occurrence($task, $date, OccurrenceState::Completed, $completion, $goal);
        } elseif ($state === OccurrenceState::Available && !$goal->isMet()) {
            $occurrences[] = new Occurrence($task, $date, OccurrenceState::Available, null, $goal);
        }

        $weekClosed = $date->toDateString() === $goal->end && $state === OccurrenceState::Expired;

        if ($weekClosed) {
            array_push($occurrences, ...array_fill(0, $goal->remaining(), new Occurrence($task, $date, OccurrenceState::Expired, null, $goal)));
        }

        return $occurrences;
    }
}
