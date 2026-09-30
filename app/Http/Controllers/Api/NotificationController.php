<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private const LIMIT = 50;

    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'items' => $user->notifications()->orderByDesc('id')->limit(self::LIMIT)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'kind' => $notification->type,
                'challenge_id' => $notification->data['challenge_id'] ?? null,
                'title' => $notification->data['title'],
                'message' => $notification->data['message'],
                'created_at' => $notification->created_at->toIso8601String(),
                'read' => $notification->read_at !== null,
            ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function preferences(Request $request)
    {
        return response()->json($request->user()->notificationPreferences());
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate(
            collect(User::NOTIFICATION_KINDS)
            ->mapWithKeys(fn ($kind) => [$kind => ['sometimes', 'boolean']])
            ->all()
        );

        $user = $request->user();
        $user->update(['notification_preferences' => [...$user->notificationPreferences(), ...$data]]);

        return response()->json($user->notificationPreferences());
    }
}
