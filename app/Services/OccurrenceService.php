<?php

namespace App\Services;

use App\Domain\Occurrence;
use App\Domain\OccurrenceRules;
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

        $completions = $participant->completions()
        ->whereDate('occurrence_date', $date)
        ->get()
        ->keyBy('task_id');

        return $challenge->tasks
        ->filter(fn (Task $task) => $task->recurrence()->occursOn($date))
        ->map(function (Task $task) use ($date, $now, $completions) {
            $completion = $completions->get($task->id);

            return new Occurrence(
                task: $task,
                date: $date,
                state: OccurrenceRules::stateFor($task, $date, $completion !== null, $now),
                completion: $completion,
            );
        })
        ->values()
        ->all();
    }

    /** @return Occurrence[] */
    public function forUserOnDate(User $user, CarbonImmutable $date, CarbonImmutable $now): array
    {
        $participants = $user->participations()
        ->with(['challenge.tasks'])
        ->get();

        $occurrences = [];

        foreach ($participants as $participant) {
            $occurrences = [
                ...$occurrences,
                ...$this->forParticipantOnDate($participant, $date, $now),
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
        ->whereBetween('occurrence_date', [$from->toDateString(), $to->toDateString()])
        ->get()
        ->keyBy(fn ($completion) => $completion->task_id . '|' . $completion->occurrence_date->toDateString());

        $occurrences = [];
        $date = $from;

        while ($date <= $to) {
            foreach ($tasks as $task) {
                if (!$task->recurrence()->occursOn($date)) {
                    continue;
                }

                $completion = $completions->get($task->id . '|' . $date->toDateString());

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
}
