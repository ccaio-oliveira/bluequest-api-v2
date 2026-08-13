<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialIdentityVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SocialAuthController extends Controller
{
    public function store(Request $request, string $provider, SocialIdentityVerifier $verifier)
    {
        if (!in_array($provider, ['google', 'apple'], true)) {
            return response()->json(['error' => 'unsupported_provider'], 404);
        }

        $data = $request->validate([
            'identity_token' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $identity = $verifier->verify($provider, $data['identity_token']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => 'invalid_identity_token'], 422);
        }

        if (!$identity->email) {
            return response()->json(['error' => 'email_not_provided'], 422);
        }

        $user = DB::transaction(function () use ($identity, $data) {
            $account = SocialAccount::where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->first();

            if ($account) {
                return $account->user;
            }

            $user = $identity->emailVerified ? User::where('email', $identity->email)->first() : null;

            $user ??= User::create([
                'name' => $identity->name ?? $data['name'] ?? 'Participante',
                'email' => $identity->email,
                'password' => null,
            ]);

            $user->socialAccounts()->create([
                'provider' => $identity->provider,
                'provider_user_id' => $identity->providerUserId,
            ]);

            return $user;
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
            'token' => $user->createToken('mobile')->plainTextToken,
        ]);
    }
}
