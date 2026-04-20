<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdvertiserController;
use App\Http\Controllers\Api\AdvertiserTaskController;
use App\Http\Controllers\Api\AdminTaskModerationController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\PerformerController;
use App\Http\Controllers\Api\PerformerTaskController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'like-sprint-backend',
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth.token')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth.token')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/sessions', [SessionController::class, 'index']);
    Route::post('/sessions/{session}/revoke', [SessionController::class, 'revoke']);

    Route::prefix('finance')->group(function () {
        Route::get('/wallet', [FinanceController::class, 'wallet']);
        Route::get('/ledger', [FinanceController::class, 'ledger']);
        Route::post('/deposits/simulate', [FinanceController::class, 'simulateDeposit']);
        Route::get('/withdrawals', [FinanceController::class, 'withdrawals']);
        Route::post('/withdrawals', [FinanceController::class, 'createWithdrawal']);
    });

    Route::middleware('role:performer')->group(function () {
        Route::get('/performer/home', [PerformerController::class, 'home']);
        Route::get('/performer/tasks/available', [PerformerTaskController::class, 'available']);
    });

    Route::middleware('role:advertiser')->group(function () {
        Route::get('/advertiser/home', [AdvertiserController::class, 'home']);
        Route::get('/advertiser/tasks', [AdvertiserTaskController::class, 'index']);
        Route::post('/advertiser/tasks', [AdvertiserTaskController::class, 'store']);
        Route::put('/advertiser/tasks/{task}', [AdvertiserTaskController::class, 'update']);
        Route::post('/advertiser/tasks/{task}/submit-moderation', [AdvertiserTaskController::class, 'submitModeration']);
        Route::post('/advertiser/tasks/{task}/launch', [AdvertiserTaskController::class, 'launch']);
        Route::post('/advertiser/tasks/{task}/pause', [AdvertiserTaskController::class, 'pause']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/tasks/moderation-queue', [AdminTaskModerationController::class, 'queue']);
        Route::post('/admin/tasks/{task}/moderate', [AdminTaskModerationController::class, 'moderate']);
    });
});
