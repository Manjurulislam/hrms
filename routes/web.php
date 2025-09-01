<?php

use App\Http\Controllers\Backend\CompanyController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\DepartmentScheduleController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\EmployeeController;
use App\Http\Controllers\Backend\HolidayController;
use App\Http\Controllers\Backend\LeaveTypeController;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;


Route::get('error-logs', [LogViewerController::class, 'index']);


Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');


    Route::controller(CompanyController::class)->name('companies.')
        ->prefix('companies')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{company}/edit', 'edit')->name('edit');
            Route::put('update/{company}', 'update')->name('update');
            Route::get('{company}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{company}', 'destroy')->name('destroy');
        });

    Route::controller(DepartmentController::class)->name('departments.')
        ->prefix('departments')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{department}/edit', 'edit')->name('edit');
            Route::put('update/{department}', 'update')->name('update');
            Route::get('{department}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{department}', 'destroy')->name('destroy');
        });

    Route::controller(DepartmentScheduleController::class)->name('department-schedules.')
        ->prefix('department-schedules')->group(function () {
            Route::post('{department}/store', 'store')->name('store');
            Route::get('{department}/edit', 'edit')->name('edit');
            Route::get('{department}/has-schedule', 'hasSchedule')->name('has-schedule');
            Route::put('update/{department}', 'update')->name('update');
        });


    Route::controller(DesignationController::class)->name('designations.')
        ->prefix('designations')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{designation}/edit', 'edit')->name('edit');
            Route::put('update/{designation}', 'update')->name('update');
            Route::get('{designation}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{designation}', 'destroy')->name('destroy');
        });

    Route::controller(HolidayController::class)->name('holidays.')
        ->prefix('holidays')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{holiday}/edit', 'edit')->name('edit');
            Route::put('update/{holiday}', 'update')->name('update');
            Route::get('{holiday}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{holiday}', 'destroy')->name('destroy');
        });

    Route::controller(LeaveTypeController::class)->name('leave-types.')
        ->prefix('leave-types')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{leaveType}/edit', 'edit')->name('edit');
            Route::put('update/{leaveType}', 'update')->name('update');
            Route::get('{leaveType}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{leaveType}', 'destroy')->name('destroy');
        });

    Route::controller(EmployeeController::class)->name('employees.')
        ->prefix('employees')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{employee}/edit', 'edit')->name('edit');
            Route::put('update/{employee}', 'update')->name('update');
            Route::get('{employee}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{employee}', 'destroy')->name('destroy');
        });


    Route::controller(UserController::class)->name('users.')
        ->prefix('users')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('get', 'get')->name('get');
            Route::get('create', 'create')->name('create');
            Route::post('store', 'store')->name('store');
            Route::get('{user}/edit', 'edit')->name('edit');
            Route::put('update/{user}', 'update')->name('update');
            Route::get('{user}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('delete/{user}', 'destroy')->name('destroy');
        });
});


require __DIR__ . '/auth.php';
