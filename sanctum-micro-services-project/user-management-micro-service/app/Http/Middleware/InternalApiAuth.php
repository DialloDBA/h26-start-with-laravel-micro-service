<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Internal-API-Key');

        if (!$apiKey || $apiKey !== config('app.internal_api_key')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}
