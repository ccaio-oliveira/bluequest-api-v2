<?php

namespace App\Models;

use App\Domain\Recurrence;
use App\Domain\RecurrenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = ['challenge_id', 'name', 'description', 'points', 'recurrence_type', 'recurrence_dates', 'recurrence_weekdays', 'deadline_time', 'photo_requirement', 'active_from', 'active_until'];

    protected function casts(): array
    {
        return [
            'recurrence_type' => RecurrenceType::class,
            'recurrence_dates' => 'array',
            'recurrence_weekdays' => 'array',
            'active_from' => 'immutable_date',
            'active_until' => 'immutable_date'
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    public function recurrence(): Recurrence
    {
        return new Recurrence(
            $this->recurrence_type,
            $this->recurrence_dates ?? [],
            $this->recurrence_weekdays ?? []
        );
    }
}
