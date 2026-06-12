<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TelegramAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('telegram-login', [TelegramAuthController::class, 'telegramLogin']);
    Route::post('telegram-auth', [TelegramAuthController::class, 'telegramApi']);
});

Route::middleware('jwt.auth')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [ProfileController::class, 'me']);
    });

    Route::post('profile', [ProfileController::class, 'update']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('today', [AttendanceController::class, 'today']);
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
    });

    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::get('{leave}', [LeaveController::class, 'show']);
        Route::delete('{leave}', [LeaveController::class, 'destroy']);
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::patch('users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        Route::get('attendances', [AttendanceController::class, 'adminIndex']);

        Route::get('leaves', [LeaveController::class, 'adminIndex']);
        Route::patch('leaves/{leave}/review', [LeaveController::class, 'review']);
    });
});
