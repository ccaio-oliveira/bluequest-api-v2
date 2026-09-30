<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

abstract class ChallengeNotification extends Notification
{
    abstract protected function kind(): string;

    public function via(User $notifiable): array
    {
        return $notifiable->wantsNotification($this->kind()) ? ['database'] : [];
    }

    public function databaseType(User $notifiable): string
    {
        return $this->kind();
    }
}
