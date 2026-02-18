<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('pickups', [PickupController::class, 'index']);
Route::post('pickups', [PickupController::class, 'store']);

Route::get('payments', [PaymentController::class, 'index']);

Route::apiResource('households', HouseholdController::class);

Route::middleware('auth:api')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::put('pickups/{id}/schedule', [PickupController::class, 'schedule']);
    Route::put('pickups/{id}/complete', [PickupController::class, 'complete']);
    Route::put('pickups/{id}/cancel', [PickupController::class, 'cancel']);

    Route::post('payments', [PaymentController::class, 'store']);
    Route::put('payments/{id}/confirm', [PaymentController::class, 'confirm']);
});
