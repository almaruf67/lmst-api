<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Default user route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('students', StudentController::class);
    Route::get('/my-students', [StudentController::class, 'myStudents']);
    Route::get('/class-rosters', [StudentController::class, 'classRoster']);

    Route::post('/attendance/bulk', [AttendanceController::class, 'recordBulk']);
    Route::get('/reports/attendance/monthly', [AttendanceController::class, 'monthlyReport']);
    Route::get('/dashboard/summary', [AttendanceController::class, 'dashboardSummary']);
});
