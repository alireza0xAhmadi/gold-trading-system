<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TransactionController;

// Test route to check API is working
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()->toISOString()
    ]);
});


Route::prefix('v1')->group(function () {
    // Order routes
    Route::post('/orders/buy', [OrderController::class, 'placeBuyOrder']);
    Route::post('/orders/sell', [OrderController::class, 'placeSellOrder']);
    Route::get('/orders/user/{userId}', [OrderController::class, 'getUserOrders']);
    Route::get('/orders/active/{type}', [OrderController::class, 'getActiveOrders']);
    Route::patch('/orders/{orderId}/cancel', [OrderController::class, 'cancelOrder']);

    // Transaction routes
    Route::get('/transactions/user/{userId}', [TransactionController::class, 'getUserTransactions']);
    Route::get('/transactions/history', [TransactionController::class, 'getTransactionHistory']);
});
