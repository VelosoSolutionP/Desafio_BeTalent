<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/login',    [AuthController::class, 'login']);
Route::post('/purchase', [TransactionController::class, 'purchase']);


Route::middleware('auth:api')->group(function () {


    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::apiResource('users', UserController::class);

    Route::apiResource('products', ProductController::class);

    Route::get('/clients',     [ClientController::class, 'index']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);

    Route::get('/transactions',           [TransactionController::class, 'index']);
    Route::get('/transactions/{id}',      [TransactionController::class, 'show']);
    Route::post('/transactions/{id}/refund', [TransactionController::class, 'refund']);

    Route::get('/gateways',                        [GatewayController::class, 'index']);
    Route::patch('/gateways/{id}/toggle',          [GatewayController::class, 'toggle']);
    Route::patch('/gateways/{id}/priority',        [GatewayController::class, 'updatePriority']);
});
