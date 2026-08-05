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
    public function login()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function me(Request $request) {}

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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
