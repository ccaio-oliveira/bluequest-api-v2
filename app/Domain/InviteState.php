<?php

namespace App\Domain;

enum InviteState: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case ChallengeClosed = 'challenge_closed';
    case AlreadyParticipant = 'already_participant';
}
