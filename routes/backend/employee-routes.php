<?php


use App\Http\Controllers\Backend\EmployeeAttendanceController;
use App\Http\Controllers\Employee\LeaveController;
use App\Http\Controllers\Employee\NoticeController as EmpNoticeController;
use App\Http\Controllers\Api\AttendanceRecordController;


Route::controller(EmployeeAttendanceController::class)->name('emp-attendance.')
    ->prefix('emp-attendance')->group(function () {
        Route::get('/', 'index')->name('index');

        // API endpoints for attendance
        Route::post('/start-work', 'startWork')->name('start-work');
        Route::post('/end-work', 'endWork')->name('end-work');
        Route::post('/start-break', 'startBreak')->name('start-break');
        Route::post('/end-break', 'endBreak')->name('end-break');
        Route::get('/current-status', 'currentStatus')->name('current-status');
        Route::get('/monthly-data', 'monthlyData')->name('monthly-data');
    });

Route::controller(LeaveController::class)->name('emp-leave.')
    ->prefix('leave')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('get', 'get')->name('get');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::post('{leaveRequest}/cancel', 'cancel')->name('cancel');
        Route::get('approvals', 'approvals')->name('approvals');
        Route::get('approvals/get', 'getApprovals')->name('approvals.get');
        Route::get('approvals/{leaveRequest}', 'showApproval')->name('approvals.show');
        Route::post('approvals/{leaveRequest}/approve', 'approve')->name('approvals.approve');
        Route::post('approvals/{leaveRequest}/reject', 'reject')->name('approvals.reject');
    });

Route::controller(EmpNoticeController::class)->name('emp-notices.')
    ->prefix('emp-notices')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('get', 'get')->name('get');
        Route::get('{notice}', 'show')->name('show');
    });

// API routes for attendance records data table
Route::prefix('api')->group(function () {
    Route::get('/attendance-records', [AttendanceRecordController::class, 'index'])->name('attendance-records.get');
    Route::get('/attendance-records/export', [AttendanceRecordController::class, 'export'])->name('attendance-records.export');
});



