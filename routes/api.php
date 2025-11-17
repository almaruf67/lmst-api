<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    // Authentication
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::prefix('profile')->group(function (): void {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
            Route::put('/password', [ProfileController::class, 'updatePassword']);
        });

        // Default user route
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::apiResource('students', StudentController::class);
        Route::get('/my-students', [StudentController::class, 'myStudents']);
        Route::get('/class-rosters', [StudentController::class, 'classRoster']);

        Route::apiResource('admins', AdminUserController::class)->except(['create', 'edit']);
        Route::get('/teachers/options', [TeacherController::class, 'options']);
        Route::apiResource('teachers', TeacherController::class)->except(['create', 'edit']);

        Route::post('/attendance/bulk', [AttendanceController::class, 'recordBulk']);
        Route::get('/reports/attendance/monthly', [AttendanceController::class, 'monthlyReport']);
        Route::get('/dashboard/summary', [AttendanceController::class, 'dashboardSummary']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/recent', [NotificationController::class, 'recent']);
        Route::get('/notifications/counts', [NotificationController::class, 'counts']);
        Route::post('/notifications/read', [NotificationController::class, 'markSelected']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'mark']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAll']);
    });
});
