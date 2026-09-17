<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

final class HistoryService
{
    public function __construct(private OccurrenceService $occurrences) {}

    /** @return array<string, \App\Domain\Occurrence[]> */
    public function occurrencesByDate(User $user, string $from, string $to, CarbonImmutable $now): array
    {
        $participants = $user->participations()->with(['challenge.tasks'])->get();
        $byDate = [];

        foreach ($participants as $participant) {
            $challenge = $participant->challenge;

            $start = max($from, $challenge->start_date->toDateString());
            $end = min($to, $challenge->end_date->toDateString());

            if ($start > $end) {
                continue;
            }

            foreach ($this->occurrences->forParticipantInRange(
                $participant,
                CarbonImmutable::parse($start),
                CarbonImmutable::parse($end),
                $now
            ) as $occurrence) {
                $byDate[$occurrence->date->toDateString()][] = $occurrence;
            }
        }

        ksort($byDate);

        return $byDate;
    }

    public function range(User $user): ?array
    {
        $challenges = $user->participations()->with('challenge')->get()->map->challenge;

        if ($challenges->isEmpty()) {
            return null;
        }

        return [
            'first' => $challenges->min(fn ($c) => $c->start_date->toDateString()),
            'last' => $challenges->max(fn ($c) => $c->end_date->toDateString()),
            'timezone' => $challenges->sortByDesc('start_date')->first()->timezone,
        ];
    }
}
