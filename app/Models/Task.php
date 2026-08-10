<?php

namespace App\Models;

use App\Domain\Recurrence;
use App\Domain\RecurrenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = ['challenge_id', 'name', 'description', 'points', 'recurrence_type', 'recurrence_date', 'recurrence_weekdays', 'deadline_time', 'photo_requirement'];

    protected function casts(): array
    {
        return [
            'recurrence_type' => RecurrenceType::class,
            'recurrence_date' => 'immutable_date',
            'recurrence_weekdays' => 'array'
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
            $this->recurrence_date,
            $this->recurrence_weekdays ?? []
        );
    }
}
