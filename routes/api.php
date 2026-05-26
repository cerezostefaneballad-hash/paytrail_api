<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SemesterReportController;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected (admin only)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get ('/me',     [AuthController::class, 'me']);

    Route::get   ('/dashboard',    [DashboardController::class, 'index']);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('admins',   AdminUserController::class);
    Route::get ('/settings', [SettingController::class, 'show']);
    Route::post('/settings', [SettingController::class, 'store']);
    Route::get('/report', [SemesterReportController::class, 'index']);
});