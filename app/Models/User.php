<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'avatar_url', 'notification_preferences'];

    public const NOTIFICATION_KINDS = ['daily_reminder', 'deadline', 'weekly_mandatory', 'joined', 'ranking', 'ended'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function createdChallenges(): HasMany
    {
        return $this->hasMany(Challenge::class, 'creator_user_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /** @return array<string, bool> */
    public function notificationPreferences(): array
    {
        $saved = $this->notification_preferences ?? [];

        return collect(self::NOTIFICATION_KINDS)
        ->mapWithKeys(fn ($kind) => [$kind => (bool) ($saved[$kind] ?? true)])
        ->all();
    }

    public function wantsNotification(string $kind): bool
    {
        return $this->notificationPreferences()[$kind];
    }
}
