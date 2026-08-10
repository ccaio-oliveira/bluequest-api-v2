<?php

namespace App\Models;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = ['user_id', 'challenge_id', 'joined_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }
}
