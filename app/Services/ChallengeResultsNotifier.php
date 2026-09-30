<?php

namespace App\Services;

use App\Models\Challenge;
use App\Notifications\ChallengeEnded;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ChallengeResultsNotifier
{
    public function __construct(private RankingService $ranking) {}

    public function notify(Challenge $challenge): void
    {
        if ($challenge->results_notified_at !== null) {
            return;
        }

        DB::transaction(function () use ($challenge) {
            foreach ($this->ranking->rank($challenge) as $participant) {
                $participant->user->notify(new ChallengeEnded($challenge, $participant->rank_position, $participant->points_total));
            }

            $challenge->update(['results_notified_at' => CarbonImmutable::now()]);
        });
    }
}
