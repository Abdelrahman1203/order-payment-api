<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::pattern('order', '[0-9]+');

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register');
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login');

    Route::middleware('auth:api')->group(function (): void {
        Route::apiResource('orders', OrderController::class);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder']);
        Route::post('orders/{order}/payments', [PaymentController::class, 'store']);
    });
});
