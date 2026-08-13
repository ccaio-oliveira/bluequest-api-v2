<?php

namespace App\Domain;

enum ChallengeState: string
{
    case Future = 'future';
    case InProgress = 'in_progress';
    case Closed = 'closed';
}
