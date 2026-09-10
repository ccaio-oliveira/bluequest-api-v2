<?php

namespace App\Services;

use App\Domain\OccurrenceState;
use App\Models\Participant;
use Carbon\CarbonImmutable;

final class ParticipantStatsService
{
    public function __construct(private OccurrenceService $occurrences) {}

    public function for(Participant $participant, CarbonImmutable $now): array
    {
        $challenge = $participant->challenge;
        $timezone = $challenge->timezone;

        $start = $this->midnight($challenge->start_date, $timezone);
        $end = $this->midnight($challenge->end_date, $timezone);
        $today = $now->setTimezone($timezone)->startOfDay();

        $to = $today->lessThan($end) ? $today : $end;

        if ($to->lessThan($start)) {
            return [
                'completed_count' => 0,
                'expired_count' => 0,
                'total_occurrences' => 0,
                'streak_days' => 0
            ];
        }

        $occurrences = $this->occurrences->forParticipantInRange($participant, $start, $to, $now);

        $completed = 0;
        $expired = 0;
        $byDate = [];

        foreach ($occurrences as $occurrence) {
            $byDate[$occurrence->date->toDateString()][] = $occurrence;

            if ($occurrence->state === OccurrenceState::Completed) {
                $completed++;
            } elseif ($occurrence->state === OccurrenceState::Expired) {
                $expired++;
            }
        }

        return [
            'completed_count' => $completed,
            'expired_count' => $expired,
            'total_occurrences' => count($occurrences),
            'streak_days' => $this->streak($byDate, $today),
        ];
    }

    private function streak(array $byDate, CarbonImmutable $today): int
    {
        $dates = array_keys($byDate);
        rsort($dates);

        $streak = 0;

        foreach ($dates as $date) {
            $dayOccurrences = collect($byDate[$date]);

            if ($dayOccurrences->every(fn ($o) => $o->state === OccurrenceState::Completed)) {
                $streak++;
                continue;
            }

            $isToday = $date === $today->toDateString();
            $stillOpen = $dayOccurrences->contains(fn ($o) => $o->state === OccurrenceState::Available);

            if ($isToday && $stillOpen) {
                continue;
            }

            break;
        }

        return $streak;
    }

    private function midnight(CarbonImmutable $date, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::create($date->year, $date->month, $date->day, 0, 0, 0, $timezone);
    }
}
