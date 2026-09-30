<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\User;

class RankingOvertaken extends ChallengeNotification
{
    public function __construct(
        private readonly Challenge $challenge,
        private readonly User $overtaker,
        private readonly int $position,
    )
    {}

    public function toArray(object $notifiable): array
    {
        return [
            'challenge_id' => $this->challenge->id,
            'title' => "{$this->overtaker->name} passou você no ranking",
            'message' => "{$this->challenge->name} · você está em {$this->position}º"
        ];
    }

    protected function kind(): string
    {
        return 'ranking';
    }
}
