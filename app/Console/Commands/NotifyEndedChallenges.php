<?php

namespace App\Console\Commands;

use App\Domain\ChallengeRules;
use App\Domain\ChallengeState;
use App\Models\Challenge;
use App\Services\ChallengeResultsNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class NotifyEndedChallenges extends Command
{
    protected $signature = 'challenges:notify-ended';

    protected $description = 'Avisa os participantes do resultado dos desafios que terminaram';

    public function handle(ChallengeResultsNotifier $notifier): void
    {
        $now = CarbonImmutable::now();

        Challenge::whereNull('results_notified_at')
        ->where('end_date', '<=', $now->toDateString())
        ->get()
        ->filter(fn (Challenge $challenge) => ChallengeRules::state($challenge, $now) === ChallengeState::Closed)
        ->each(fn (Challenge $challenge) => $notifier->notify($challenge));
    }
}
