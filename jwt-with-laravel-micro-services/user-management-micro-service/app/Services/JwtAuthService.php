<?php

namespace App\Services;

use App\Contracts\JwtContract;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Override;
use phpDocumentor\Reflection\Types\This;

class JwtAuthService implements JwtContract
{
    private string $privateKey;

    private string $publicKey;
    private string $iss;

    private string $algorithm = 'RS256';

    public function __construct()
    {
        $this->privateKey = file_get_contents(storage_path('keys/private.pem'));
        $this->publicKey = file_get_contents(storage_path('keys/public.pem'));
        $this->iss = config('services.auth.issuer', 'api.exemple.com');
    }

    #[Override]
    public function generateToken(User $user)
    {
        $issuerAt = time();
        $exp = $issuerAt + 3600;

        $payload = [
            'iss' => $this->iss,
            'aud' => 'orders.exemple.com',
            'sub' => 'user_id_' . $user->id,
            'iat' => $issuerAt,
            'exp' => $exp,
            'user' => $user->toArray(),
        ];
        return [$exp, JWT::encode($payload, $this->privateKey, $this->algorithm)];
    }

    #[Override]
    public function validateToken(string $token): bool
    {
        return false;
    }

    #[Override]
    public function decodeToken(string $token): \stdClass
    {
        return JWT::decode($token, new Key($this->publicKey, $this->algorithm));
    }

    public function publicKey()
    {
        return $this->publicKey;
    }
    public function getAlgo()
    {
        return $this->algorithm;
    }

    public function getIss()
    {
        return $this->iss;
    }
}
