<?php

namespace App\Http\Middleware;

use App\Services\AuthServiceClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMicroservice
{

    public function __construct(private AuthServiceClient $authServiceClient)
    {
        //
    }
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (empty($token)) {
            Log::warning('No token provided in the request.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $isValid = $this->authServiceClient->validateToken($token);

        if (!$isValid || !$isValid['valid']) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->attributes->add(['user' => $isValid['user']]);

        return $next($request);
    }
}
