<?php

namespace App\Models;

use App\Domain\Recurrence;
use App\Domain\RecurrenceType;
use App\Domain\TaskRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = ['challenge_id', 'name', 'description', 'points', 'recurrence_type', 'recurrence_dates', 'recurrence_weekdays', 'recurrence_times_per_week', 'deadline_time', 'photo_requirement', 'active_from', 'active_until'];

    protected function casts(): array
    {
        return [
            'recurrence_type' => RecurrenceType::class,
            'recurrence_dates' => 'array',
            'recurrence_weekdays' => 'array',
            'recurrence_times_per_week' => 'integer',
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
            $this->recurrence_weekdays ?? [],
            $this->recurrence_times_per_week ?? 0,
        );
    }

    public static function recurrenceAttributes(array $data): array
    {
        $type = RecurrenceType::from($data['recurrence_type']);

        return [
            'recurrence_type' => $type,
            'recurrence_weekdays' => $type === RecurrenceType::Weekdays ? array_values(array_unique($data['recurrence_weekdays'])) : null,
            'recurrence_dates' => $type === RecurrenceType::Dates ? TaskRules::sortedDates($data['recurrence_dates']) : null,
            'recurrence_times_per_week' => $type === RecurrenceType::Weekly ? (int) $data['recurrence_times_per_week'] : null
        ];
    }
}
