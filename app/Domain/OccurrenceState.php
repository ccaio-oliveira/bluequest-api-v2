<?php

namespace App\Domain;

enum OccurrenceState: string
{
    case Future = 'future';
    case Available = 'available';
    case Completed = 'completed';
    case Expired = 'expired';
}
