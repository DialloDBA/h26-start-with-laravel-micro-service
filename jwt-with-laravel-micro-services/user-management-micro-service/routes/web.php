<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'User Management Microservice is running']);
});

Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);