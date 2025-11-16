<?php

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthCheckResultsController::class)->name('health.dashboard');
Route::get('/health/json', HealthCheckJsonResultsController::class)->name('health.json');
