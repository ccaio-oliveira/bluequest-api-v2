<?php

namespace App\Domain;

use App\Models\Invite;

final class InviteRules
{
    public static function state(
        ?Invite $invite,
        ?ChallengeState $challengeState,
        bool $isAlreadyParticipant,
        bool $wasRemoved,
    ): InviteState {
        if ($invite === null || $invite->revoked_at !== null || $wasRemoved) {
            return InviteState::Invalid;
        }

        if ($isAlreadyParticipant) {
            return InviteState::AlreadyParticipant;
        }

        if ($challengeState === ChallengeState::Closed) {
            return InviteState::ChallengeClosed;
        }

        return InviteState::Valid;
    }
}
