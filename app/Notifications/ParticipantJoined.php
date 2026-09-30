<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\User;

class ParticipantJoined extends ChallengeNotification
{
    public function __construct(
        private readonly Challenge $challenge,
        private readonly User $newcomer,
        private readonly int $participantsCount,
    )
    {}

    public function toArray(object $notifiable): array
    {
        return [
            'challenge_id' => $this->challenge->id,
            'title' => "{$this->newcomer->name} entrou no desafio",
            'message' => "{$this->challenge->name} · {$this->participantsCount} participantes",
        ];
    }

    protected function kind(): string
    {
        return 'joined';
    }
}
