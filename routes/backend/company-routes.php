<?php

use App\Http\Controllers\Company\AttendanceController;
use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\DepartmentController;
use App\Http\Controllers\Company\DesignationController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\HolidayController;
use App\Http\Controllers\Company\LeaveRequestController;
use App\Http\Controllers\Company\LeaveTypeController;

Route::prefix('company')->name('company.')->group(function () {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('switch', [\App\Http\Controllers\Company\CompanySwitchController::class, 'switch'])
        ->name('switch');

    Route::controller(AttendanceController::class)->name('attendance.')
        ->prefix('attendance')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('export', 'export')->name('export');
            Route::get('{employee}/show', 'show')->name('show');
            Route::get('{employee}/records', 'records')->name('records');
        });

    Route::controller(DepartmentController::class)->name('departments.')
        ->prefix('departments')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{department}/edit', 'edit')->name('edit');
            Route::put('update/{department}', 'update')->name('update');
            Route::post('{department}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{department}', 'destroy')->name('destroy');
        });

    Route::controller(DesignationController::class)->name('designations.')
        ->prefix('designations')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{designation}/edit', 'edit')->name('edit');
            Route::put('update/{designation}', 'update')->name('update');
            Route::post('{designation}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{designation}', 'destroy')->name('destroy');
        });

    Route::controller(EmployeeController::class)->name('employees.')
        ->prefix('employees')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{employee}/edit', 'edit')->name('edit');
            Route::put('update/{employee}', 'update')->name('update');
            Route::post('{employee}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{employee}', 'destroy')->name('destroy');
        });

    Route::controller(HolidayController::class)->name('holidays.')
        ->prefix('holidays')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{holiday}/edit', 'edit')->name('edit');
            Route::put('update/{holiday}', 'update')->name('update');
            Route::post('{holiday}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{holiday}', 'destroy')->name('destroy');
        });

    Route::controller(LeaveRequestController::class)->name('leave-requests.')
        ->prefix('leave-requests')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('{leaveRequest}/show', 'show')->name('show');
            Route::post('{leaveRequest}/approve', 'approve')->name('approve');
            Route::post('{leaveRequest}/reject', 'reject')->name('reject');
        });

    Route::controller(LeaveTypeController::class)->name('leave-types.')
        ->prefix('leave-types')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{leaveType}/edit', 'edit')->name('edit');
            Route::put('update/{leaveType}', 'update')->name('update');
            Route::post('{leaveType}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{leaveType}', 'destroy')->name('destroy');
        });
});
