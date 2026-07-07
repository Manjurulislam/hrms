<?php

use App\Http\Controllers\Api\Leave\ApplyLeaveController;
use App\Http\Controllers\Api\Leave\CancelLeaveController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;
use App\Http\Controllers\Api\Leave\LeaveListController;
use App\Http\Controllers\Api\Leave\LeaveTypeListController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('leave')->group(function () {
    Route::get('/', LeaveListController::class);
    Route::get('types', LeaveTypeListController::class);
    Route::get('balance', LeaveBalanceController::class);
    Route::post('/', ApplyLeaveController::class);
    Route::post('{leaveRequest}/cancel', CancelLeaveController::class);
    // approval routes appended in Task 7
});
