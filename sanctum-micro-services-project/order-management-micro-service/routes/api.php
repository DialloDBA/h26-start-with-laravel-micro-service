<?php

use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function (Request $request) {
    return response()->json([
        'message' => 'Order Management Microservice is working!',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->attributes->get('user');
})->middleware('interservice.auth');



Route::controller(OrderController::class)->middleware("interservice.auth")->group(function () {
    Route::get("orders", "index");
    Route::post('/orders/create', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/orders/number/{orderNumber}', [OrderController::class, 'getByNumber']);
});
