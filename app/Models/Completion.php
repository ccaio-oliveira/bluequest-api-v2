<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Completion extends Model
{
    protected $fillable = ['participant_id', 'task_id', 'occurrence_date', 'completed_at', 'points_awarded', 'photo_url'];

    protected function casts(): array
    {
        return [
            'occurrence_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
