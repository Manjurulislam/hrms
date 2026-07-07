<?php

use App\Http\Controllers\Api\Attendance\CheckInController;
use App\Http\Controllers\Api\Attendance\CheckOutController;
use App\Http\Controllers\Api\Attendance\MonthlyController;
use App\Http\Controllers\Api\Attendance\RecordsController;
use App\Http\Controllers\Api\Attendance\TodayStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('attendance')->group(function () {
    Route::get('today', TodayStatusController::class);
    Route::post('check-in', CheckInController::class);
    Route::post('check-out', CheckOutController::class);
    Route::get('monthly', MonthlyController::class);
    Route::get('records', RecordsController::class);
});
