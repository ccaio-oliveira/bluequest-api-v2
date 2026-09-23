<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Completion;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    private const PAGE_SIZE = 30;

    public function index(Request $request, Challenge $challenge)
    {
        $user = $request->user();

        abort_unless($challenge->participants()->where('user_id', $user->id)->exists(), 403);

        $data = $request->validate(['before' => ['nullable', 'integer']]);

        $query = Completion::query()
        ->join('participants', 'participants.id', '=', 'completions.participant_id')
        ->join('users', 'users.id', '=', 'participants.user_id')
        ->join('tasks', 'tasks.id', '=', 'completions.task_id')
        ->where('participants.challenge_id', '=', $challenge->id)
        ->whereNull('participants.removed_at')
        ->select('completions.*', 'users.id as author_id', 'users.name as author_name', 'tasks.name as task_name')
        ->orderByDesc('completions.completed_at')
        ->orderByDesc('completions.id');

        if (isset($data['before']) && $cursor = Completion::find($data['before'])) {
            $query->whereRaw(
                '(completions.completed_at, completions.id) < (?, ?)',
                [$cursor->getRawOriginal('completed_at'), $cursor->id]
            );
        }

        $rows = $query->limit(self::PAGE_SIZE + 1)->get();
        $hasMore = $rows->count() > self::PAGE_SIZE;
        $rows = $rows->take(self::PAGE_SIZE);

        return response()->json([
            'items' => $rows->map(fn ($completion) => [
                'id' => $completion->id,
                'name' => $completion->author_name,
                'is_you' => (int) $completion->author_id === $user->id,
                'task_name' => $completion->task_name,
                'points' => $completion->points_awarded,
                'occurrence_date' => $completion->occurrence_date->toDateString(),
                'completed_at' => $completion->completed_at->toIso8601String(),
                'photo_url' => $completion->photo_path === null ? null : $request->getSchemeAndHttpHost() . '/storage/' . $completion->photo_path,
            ])->values(),
            'next_before' => $hasMore ? $rows->last()->id : null
        ]);
    }
}
