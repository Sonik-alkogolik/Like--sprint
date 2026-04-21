<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdvertiserController;
use App\Http\Controllers\Api\AdvertiserSubmissionController;
use App\Http\Controllers\Api\AdvertiserTaskController;
use App\Http\Controllers\Api\AdminTaskModerationController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PerformerController;
use App\Http\Controllers\Api\PerformerSubmissionController;
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

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/{notification}/read', [NotificationController::class, 'markRead']);
    });

    Route::middleware('role:performer')->group(function () {
        Route::get('/performer/home', [PerformerController::class, 'home']);
        Route::get('/performer/tasks/available', [PerformerTaskController::class, 'available']);
        Route::post('/performer/tasks/{task}/take', [PerformerSubmissionController::class, 'takeTask']);
        Route::get('/performer/assignments/{assignment}', [PerformerSubmissionController::class, 'show']);
        Route::post('/performer/assignments/{assignment}/submit', [PerformerSubmissionController::class, 'submit']);
        Route::post('/performer/assignments/{assignment}/cancel', [PerformerSubmissionController::class, 'cancel']);
        Route::get('/performer/submissions/pending', [PerformerSubmissionController::class, 'pending']);
        Route::post('/performer/submissions/{submission}/dispute', [PerformerSubmissionController::class, 'dispute']);
    });

    Route::middleware('role:advertiser')->group(function () {
        Route::get('/advertiser/home', [AdvertiserController::class, 'home']);
        Route::get('/advertiser/tasks', [AdvertiserTaskController::class, 'index']);
        Route::post('/advertiser/tasks', [AdvertiserTaskController::class, 'store']);
        Route::put('/advertiser/tasks/{task}', [AdvertiserTaskController::class, 'update']);
        Route::post('/advertiser/tasks/{task}/submit-moderation', [AdvertiserTaskController::class, 'submitModeration']);
        Route::post('/advertiser/tasks/{task}/launch', [AdvertiserTaskController::class, 'launch']);
        Route::post('/advertiser/tasks/{task}/pause', [AdvertiserTaskController::class, 'pause']);
        Route::get('/advertiser/tasks/{task}/reports/pending', [AdvertiserSubmissionController::class, 'pendingByTask']);
        Route::post('/advertiser/submissions/{submission}/approve', [AdvertiserSubmissionController::class, 'approve']);
        Route::post('/advertiser/submissions/{submission}/reject', [AdvertiserSubmissionController::class, 'reject']);
        Route::post('/advertiser/submissions/{submission}/rework', [AdvertiserSubmissionController::class, 'rework']);
        Route::post('/advertiser/tasks/{task}/reports/mass-approve', [AdvertiserSubmissionController::class, 'massApprove']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/tasks/moderation-queue', [AdminTaskModerationController::class, 'queue']);
        Route::post('/admin/tasks/{task}/moderate', [AdminTaskModerationController::class, 'moderate']);
        Route::post('/admin/notifications/dispatch', [NotificationController::class, 'dispatchQueue']);
        Route::get('/admin/notifications/stats', [NotificationController::class, 'queueStats']);
    });
});
