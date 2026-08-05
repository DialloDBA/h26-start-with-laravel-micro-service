<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Authentication routes

Route::controller(\App\Http\Controllers\AuthController::class)->group(function () {
    Route::post('/register', "register");
    Route::post('/login', "login");
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/me', 'me');
        Route::get('/introspect', 'introspect');
    });
});

Route::get('/', function () {
    return response()->json([
        'message' => 'User Management Micro Service is running',
    ]);
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::middleware(['is_admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });
});

// Route::fallback(function () {
//     return response()->json([
//         'message' => 'Not Found',
//     ], 404);
// });
