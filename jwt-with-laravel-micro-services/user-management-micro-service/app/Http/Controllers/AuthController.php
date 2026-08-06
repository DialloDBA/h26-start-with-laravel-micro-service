<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\JwtAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private JwtAuthService $jwt) {}
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where("username", $validated['username'])->orWhere("email", $validated["username"])->first();

        if (!$user || Hash::check($validated["password"], $user->passowrd)) {
            throw ValidationException::withMessages([
                "username" => "identifiants incorrectes"
            ]);
        }
        [$exp, $token] = $this->jwt->generateToken($user);

        return response()->json(
            [
                "user" => $user->toArray(),
                'token_type' => "Bearer",
                'token' => $token,
                'expire_at' => $exp,
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::firstOrcreate([
            "name" => $validated["name"],
            "username" => $validated["username"],
            "email" => $validated["email"],
            "password" => Hash::make($validated["password"]),
        ]);

        [$exp, $token] = $this->jwt->generateToken($user);
        return response()->json(
            [
                "user" => $user->toArray(),
                'token_type' => "Bearer",
                'token' => $token,
                'expire_at' => $exp,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function logout(Request $request) {
        //nous laissons ceci du coté de frontend.
    }

    /**
     * Display the specified resource.
     */
    public function me(Request $request)
    {
        return response()->json($request->attributes->get("jwt_user")->user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function introspect(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = User::find($request->attributes->get("jwt_user")->user->id);
        [$exp, $token] = $this->jwt->generateToken($user);

        return response()->json(
            [
                "user" => $user->toArray(),
                'token_type' => "Bearer",
                'token' => $token,
                'expire_at' => $exp,
            ]
        );
    }
}
