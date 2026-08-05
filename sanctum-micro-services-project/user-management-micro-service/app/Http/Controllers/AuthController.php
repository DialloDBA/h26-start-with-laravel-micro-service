<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken("{$user->username}-token")->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => "Bearer",
        ]);
    }

    /**
     * Register a new user and return a token.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken("{$user->username}-token")->plainTextToken;
        return response()->json([
            'message' => 'Utilisateur Crée avec succès',
            'user' => $user,
            'token' => $token,
            'token_type' => "Bearer",
        ], 201);
    }

    /**
     * Logout the user and invalidate the token.
     */
    public function logout(Request $request)
    {
        if (!$request->user() || !$request->user()->currentAccessToken()) {
            return response()->json(['message' => 'No active token found'], 400);
        }
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Deconnexion réussie'], 200);
    }

    /**
     * Display the specified resource.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->sethidden(['is_admin']);
        return response()->json(['user' => $user], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function introspect(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'No token provided'], 401);
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token has expired'], 401);
        }

        $user = $accessToken->tokenable;


        return response()->json([
            "valid" => true,
            "token" => $accessToken->token,
            "abilities" => $accessToken->abilities,
            "expires_at" => $accessToken->expires_at,
            'user' => $user,
        ], 200);
    }
}
