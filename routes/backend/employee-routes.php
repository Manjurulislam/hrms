<?php


use App\Http\Controllers\Backend\EmployeeAttendanceController;


Route::controller(EmployeeAttendanceController::class)->name('emp-attendance.')
    ->prefix('emp-attendance')->group(function () {
        Route::get('/', 'index')->name('index');

    });



