<?php

namespace App\Domain;

use App\Models\Completion;
use App\Models\Task;
use Carbon\CarbonImmutable;

final class Occurrence
{
    public function __construct(
        public readonly Task $task,
        public readonly CarbonImmutable $date,
        public readonly OccurrenceState $state,
        public readonly ?Completion $completion,
    )
    {}
}
