<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\FollowUpController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'time' => now()->toIso8601String(),
]));

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware('auth.jwt')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');

        Route::patch('leads/{lead}/stage', [LeadController::class, 'changeStage']);
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
        Route::apiResource('leads', LeadController::class);

        Route::apiResource('customers', CustomerController::class);

        Route::patch('follow-ups/{follow_up}/complete', [FollowUpController::class, 'complete']);
        Route::apiResource('follow-ups', FollowUpController::class)->except('show');

        Route::prefix('analytics')->controller(AnalyticsController::class)->group(function () {
            Route::get('dashboard', 'dashboard');
            Route::get('funnel', 'funnel');
            Route::get('sources', 'sources');
            Route::get('leaderboard', 'leaderboard');
        });
    });
});
