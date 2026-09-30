<?php

namespace App\Domain;

use App\Models\Task;
use Carbon\CarbonImmutable;

final class WeeklyGoal
{
    private function __construct(
        public readonly string $start,
        public readonly string $end,
        public readonly int $target,
        public readonly int $done,
    ) {}

    /** @param string[] $completedDays dias (Y-m-d) em que a tarefa foi concluída */
    public static function for(Task $task, CarbonImmutable $date, array $completedDays): self
    {
        $challenge = $task->challenge;

        $start = max(
            $date->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
            $challenge->start_date->toDateString(),
            $task->active_from?->toDateString() ?? '',
        );

        $end = min(
            $date->endOfWeek(CarbonImmutable::SUNDAY)->toDateString(),
            $challenge->end_date->toDateString(),
            $task->active_until?->toDateString() ?? '9999-12-31',
        );

        $days = (int) CarbonImmutable::parse($start)->diffInDays(CarbonImmutable::parse($end)) + 1;
        $target = (int) ceil($task->recurrence()->timesPerWeek * $days / 7);
        $done = count(array_filter($completedDays, fn ($day) => $day >= $start && $day <= $end));

        return new self($start, $end, $target, $done);
    }

    public function remaining(): int
    {
        return max(0, $this->target - $this->done);
    }

    public function isMet(): bool
    {
        return $this->remaining() === 0;
    }

    public function daysLeft(string $today): int
    {
        return (int) CarbonImmutable::parse($today)->diffInDays(CarbonImmutable::parse($this->end)) + 1;
    }

    public function isMandatory(string $today): bool
    {
        return !$this->isMet() && $this->daysLeft($today) <= $this->remaining();
    }
}
