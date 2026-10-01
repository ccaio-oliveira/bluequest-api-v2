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
use App\Models\Participant;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;

final class ReminderService
{
    private const HORIZON_DAYS = 7;
    private const DAILY_TIME = '08:00';
    private const WEEKLY_TIME = '09:00';

    public function __construct(private OccurrenceService $occurrences) {}

    /** @return array<int, array{id: string, kind: string, fire_at: CarbonImmutable, title: string, body: string}> */
    public function upcoming(User $user, string $timezone, CarbonImmutable $now): array
    {
        $wants = $user->notificationPreferences();
        $reminders = [];
        $daily = [];

        foreach ($user->participations()->with('challenge.tasks')->get() as $participant) {
            $challenge = $participant->challenge;

            if (ChallengeRules::state($challenge, $now) === ChallengeState::Closed) {
                continue;
            }

            $today = $now->setTimezone($challenge->timezone)->toDateString();
            $from = max($today, $challenge->start_date->toDateString());
            $to = min(
                CarbonImmutable::parse($today)->addDays(self::HORIZON_DAYS - 1)->toDateString(),
                $challenge->end_date->toDateString()
            );

            if ($from > $to) {
                continue;
            }

            $occurrences = $this->occurrences->forParticipantInRange(
                $participant,
                CarbonImmutable::parse($from),
                CarbonImmutable::parse($to),
                $now
            );

            foreach ($occurrences as $occurrence) {
                if (!$this->isPending($occurrence)) {
                    continue;
                }

                $date = $occurrence->date->toDateString();
                $daily[$date]['count'] = ($daily[$date]['count'] ?? 0) + 1;
                $daily[$date]['challenges'][$challenge->name] = true;

                if ($wants['deadline']) {
                    $reminders[] = $this->deadlineReminder($occurrence);
                }
            }

            if ($wants['weekly_mandatory']) {
                array_push($reminders, ...$this->weeklyReminders($participant, $today, $to));
            }
        }

        if ($wants['daily_reminder']) {
            foreach ($daily as $date => $summary) {
                $count = $summary['count'];

                $reminders[] = [
                    'id' => "daily:$date",
                    'kind' => 'daily_reminder',
                    'fire_at' => CarbonImmutable::parse("$date " . self::DAILY_TIME, $timezone),
                    'title' => $count === 1 ? '1 tarefa hoje' : "$count tarefas hoje",
                    'body' => implode(' · ', array_keys($summary['challenges']))
                ];
            }
        }

        $upcoming = array_filter($reminders, fn ($reminder) => $reminder['fire_at'] > $now);
        usort($upcoming, fn ($a, $b) => $a['fire_at'] <=> $b['fire_at']);

        return $upcoming;
    }

    private function isPending(Occurrence $occurrence): bool
    {
        if (!in_array($occurrence->state, [OccurrenceState::Available, OccurrenceState::Future], true)) {
            return false;
        }

        return $occurrence->weekly === null || $occurrence->weekly->isMandatory($occurrence->date->toDateString());
    }

    private function deadlineReminder(Occurrence $occurrence): array
    {
        $task = $occurrence->task;

        return [
            'id' => "deadline:{$task->id}:{$occurrence->date->toDateString()}",
            'kind' => 'deadline',
            'fire_at' => OccurrenceRules::deadlineFor($task, $occurrence->date)->subHour(),
            'title' => "{$task->name} expira em 1h",
            'body' => "{$task->challenge->name} · +{$task->points} pts",
        ];
    }

    private function weeklyReminders(Participant $participant, string $today, string $to): array
    {
        $challenge = $participant->challenge;
        $reminders = [];

        $tasks = $challenge->tasks->filter(
            fn (Task $task) => $task->recurrence_type === RecurrenceType::Weekly && TaskRules::isCurrent($task, $today)
        );

        foreach ($tasks as $task) {
            $completedDays = $participant->completions()
            ->where('task_id', $task->id)
            ->get()
            ->map(fn ($completion) => $completion->occurrence_date->toDateString())
            ->all();

            foreach ([$today, $to] as $day) {
                $goal = WeeklyGoal::for($task, CarbonImmutable::parse($day), $completedDays);

                if ($goal->start > $goal->end || $goal->isMet()) {
                    continue;
                }

                $alertDay = max(
                    $goal->start,
                    CarbonImmutable::parse($goal->end)->subDays($goal->remaining() - 1)->toDateString(),
                );

                if ($alertDay < $today) {
                    continue;
                }

                $days = $goal->daysLeft($alertDay);
                $id = "weekly:{$task->id}:{$goal->start}";

                $reminders[$id] = [
                    'id' => $id,
                    'kind' => 'weekly_mandatory',
                    'fire_at' => CarbonImmutable::parse("$alertDay " . self::WEEKLY_TIME, $challenge->timezone),
                    'title' => "{$task->name} virou obrigatória",
                    'body' => "Faltam {$goal->remaining()} em " . ($days === 1 ? '1 dia' : "$days dias") . " · {$challenge->name}",
                ];
            }
        }

        return array_values($reminders);
    }
}
