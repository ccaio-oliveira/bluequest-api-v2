<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\User;

class ChallengeEnded extends ChallengeNotification
{
    public function __construct(
        private readonly Challenge $challenge,
        private readonly int $position,
        private readonly int $points,
    )
    {}

    public function toArray(object $notifiable): array
    {
        return [
            'challenge_id' => $this->challenge->id,
            'title' => "{$this->challenge->name} terminou",
            'message' => "Você ficou em {$this->position}º com {$this->points} pts",
        ];
    }

    protected function kind(): string
    {
        return 'ended';
    }
}
