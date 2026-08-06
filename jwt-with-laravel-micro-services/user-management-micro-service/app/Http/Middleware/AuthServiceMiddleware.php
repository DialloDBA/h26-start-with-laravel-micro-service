<?php

namespace App\Http\Middleware;

use App\Services\JwtAuthService;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthServiceMiddleware
{
    public function __construct(private JwtAuthService $jwt) {}
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(["erreor" => "Token Manquants", 401]);
        }
        try {
            $decoded = $this->jwt->decodeToken($token);
            if ($decoded->iss != $this->jwt->getIss()) {
                return response()->json(["error" => "token Invalide 1"], 401);
            }
            $request->attributes->add(["jwt_user" => $decoded]);
        } catch (ExpiredException $e) {
            return response()->json(["error" => "Token invalid"], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json(["error" => "Signature invalide"], 401);
        } catch (\Exception $e) {
            return response()->json(["error" => "Token invalide"], 401);
        }

        return $next($request);
    }
}
