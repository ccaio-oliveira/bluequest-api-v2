<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    protected $fillable = ['challenge_id', 'created_by_user_id', 'code', 'used_by_user_id', 'used_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
