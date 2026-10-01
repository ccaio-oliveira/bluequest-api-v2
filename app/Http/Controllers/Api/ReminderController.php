<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request, ReminderService $reminders)
    {
        $data = $request->validate(['tz' => ['required', 'timezone']]);

        $upcoming = $reminders->upcoming($request->user(), $data['tz'], CarbonImmutable::now());

        return response()->json([
            'reminders' => array_map(fn ($reminder) => [
                ...$reminder,
                'fire_at' => $reminder['fire_at']->utc()->toIso8601String(),
            ], $upcoming)
        ]);
    }
}
