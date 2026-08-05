<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AuthServiceClient
{
    private string $baseUrl;
    private string $internalApiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.auth_service.base_url');
        $this->internalApiKey = config('services.auth_service.internal_api_key');
    }

    public function validateToken(string $token)
    {
        if (empty($token) || is_null($token)) {
            Log::warning('No token provided for validation.');
            throw new InvalidArgumentException('Token cannot be empty.');
        }
        $url = $this->baseUrl . '/api/auth/introspect';
        $cacheKey = 'auth_service_token_' . md5($token);

        if ($cachedResponse = cache()->get($cacheKey)) {
            return $cachedResponse;
        }

        $response = Http::withHeaders([
            'X-Internal-Api-Key' => $this->internalApiKey,
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->post($url, [
            'token' => $token,
        ]);

        Log::info('AuthServiceClient response: ' . $response->body());

        $data = $response->json();
        cache()->put($cacheKey, $data, config('services.auth_service.cache_duration', now()->addMinutes(5)));
        return $data;
    }
}
