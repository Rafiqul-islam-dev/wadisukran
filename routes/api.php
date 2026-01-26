<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CheckWinnerController;

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});



Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/products', [ProductController::class, 'apiIndex']);
    Route::get('/products/{id}', [ProductController::class, 'getProduct']);
    Route::post('/orders', [OrderController::class, 'orderStore']);
    Route::get('/orders/{id}', [OrderController::class, 'orderInfo']);
    Route::post('/orders-update/{id}', [OrderController::class, 'orderUpdate']);
    Route::get('/user-orders', [OrderController::class, 'apiOrdersByUser']);
    Route::get('/banner', [BannerController::class, 'getBanner']);
    Route::get('/check-win-by-invoice/{invoice_no}', [CheckWinnerController::class, 'checkWin']);
    Route::post('/claim-win', [CheckWinnerController::class, 'claimWin']);
});
