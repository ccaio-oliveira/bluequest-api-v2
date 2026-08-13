<?php

namespace App\Services;

use App\Domain\SocialIdentity;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SocialIdentityVerifier
{
    public function verify(string $provider, string $identityToken): SocialIdentity
    {
        $config = config("social.$provider");

        if (!$config) {
            throw new RuntimeException("Provedor desconhecido: $provider");
        }

        if (empty($config['audiences'])) {
            throw new RuntimeException("Client ID não configurado para $provider");
        }

        $payload = JWT::decode($identityToken, $this->publicKeys($provider, $config['jwks_url']));

        if (!in_array($payload->iss ?? '', $config['issuers'], true)) {
            throw new RuntimeException('Emissor inválido');
        }

        if (!in_array($payload->aud ?? '', $config['audiences'], true)) {
            throw new RuntimeException('Audiência inválida');
        }

        return new SocialIdentity(
            provider: $provider,
            providerUserId: $payload->sub,
            email: $payload->email ?? null,
            emailVerified: filter_var($payload->email_verified ?? false, FILTER_VALIDATE_BOOLEAN),
            name: $payload->name ?? null,
        );
    }

    private function publicKeys(string $provider, string $url): array
    {
        $jwks = Cache::remember("social_jwks_$provider", now()->addHour(), function () use ($url) {
            $response = Http::timeout(5)->get($url);

            if ($response->failed()) {
                throw new RuntimeException('Não foi possível obter as chaves públicas');
            }

            return $response->json();
        });

        return JWK::parseKeySet($jwks);
    }
}
