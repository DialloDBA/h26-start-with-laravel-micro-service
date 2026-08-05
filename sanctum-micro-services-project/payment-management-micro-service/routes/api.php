<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return response()->json([
        'message' => 'Payment Management Microservice is working!',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->attributes->get('user');
})->middleware('interservice.auth');

Route::controller(PaymentController::class)->middleware('interservice.auth')->group(function () {
    Route::get('payments', 'index');
    Route::post('/payments/create', [PaymentController::class, 'store']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::get('/payments/{id}/order', [PaymentController::class, 'showOrder']);
    Route::get('/payments/number/{paymentNumber}', [PaymentController::class, 'getByNumber']);
});
