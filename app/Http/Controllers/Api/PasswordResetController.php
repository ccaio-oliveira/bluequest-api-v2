<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    private const EXPIRES_IN_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;

    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $data['email'])->first();

        if ($user !== null) {
            defer(function () use ($user) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($code), 'created_at' => now()]
                );

                RateLimiter::clear($this->attemptsKey($user->email));

                $user->notify(new PasswordResetCode($code, self::EXPIRES_IN_MINUTES));
            });
        }

        return response()->noContent();
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        $user = User::where('email', $data['email'])->first();
        $row = $user === null ? null : DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if ($row === null || CarbonImmutable::parse($row->created_at)->addMinutes(self::EXPIRES_IN_MINUTES)->isPast()) {
            return $this->invalidCode();
        }

        $key = $this->attemptsKey($user->email);

        if (!Hash::check($data['code'], $row->token)) {
            RateLimiter::hit($key, self::EXPIRES_IN_MINUTES * 60);

            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }

            return $this->invalidCode();
        }

        DB::transaction(function () use ($user, $data) {
            $user->update(['password' => Hash::make($data['password'])]);
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        });

        RateLimiter::clear($key);

        return response()->json([
            'user' => $user->profilePayload(),
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }

    private function invalidCode()
    {
        return response()->json(['error' => 'invalid_reset_code'], 422);
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset:' . strtolower($email);
    }
}
