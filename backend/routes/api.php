<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdvertiserController;
use App\Http\Controllers\Api\PerformerController;
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

    Route::middleware('role:performer')->group(function () {
        Route::get('/performer/home', [PerformerController::class, 'home']);
    });

    Route::middleware('role:advertiser')->group(function () {
        Route::get('/advertiser/home', [AdvertiserController::class, 'home']);
    });
});
