<?php


use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ProfileController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;


Route::get('error-logs', [LogViewerController::class, 'index']);


Route::middleware(['auth', 'menu.permission'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index')->name('profile');
        Route::put('/', 'update')->name('profile.update');
        Route::put('password', 'changePassword')->name('profile.password');
    });

    //admin routes
    require __DIR__ . '/backend/admin-routes.php';
    require __DIR__ . '/backend/employee-routes.php';

});


require __DIR__ . '/auth.php';
