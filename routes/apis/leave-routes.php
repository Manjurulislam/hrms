<?php

use App\Http\Controllers\Api\Leave\ApplyLeaveController;
use App\Http\Controllers\Api\Leave\ApprovalListController;
use App\Http\Controllers\Api\Leave\ApproveLeaveController;
use App\Http\Controllers\Api\Leave\CancelLeaveController;
use App\Http\Controllers\Api\Leave\LeaveBalanceController;
use App\Http\Controllers\Api\Leave\LeaveListController;
use App\Http\Controllers\Api\Leave\LeaveTypeListController;
use App\Http\Controllers\Api\Leave\RejectLeaveController;
use App\Http\Controllers\Api\Leave\ShowApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('leave')->group(function () {
    Route::get('/', LeaveListController::class);
    Route::get('types', LeaveTypeListController::class);
    Route::get('balance', LeaveBalanceController::class);
    Route::post('/', ApplyLeaveController::class);

    // Manager approvals — static "approvals" segment registered before the
    // "{leaveRequest}" wildcard so it is never captured by it.
    Route::prefix('approvals')->group(function () {
        Route::get('/', ApprovalListController::class);
        Route::get('{leaveRequest}', ShowApprovalController::class);
        Route::post('{leaveRequest}/approve', ApproveLeaveController::class);
        Route::post('{leaveRequest}/reject', RejectLeaveController::class);
    });

    Route::post('{leaveRequest}/cancel', CancelLeaveController::class);
});
