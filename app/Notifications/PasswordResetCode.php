<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly int $minutes
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
        ->subject('Seu código para redefinir a senha')
        ->greeting("Olá, {$notifiable->name}!")
        ->line('Use este código no app BlueQuest para criar uma nova senha:')
        ->line("**{$this->code}**")
        ->line("Ele vale por {$this->minutes} minutos.")
        ->line('Se não foi você que pediu, ignore este e-mail. Sua senha continua a mesma.');
    }
}
