<?php


use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ProfileController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth', 'menu.permission'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'index')->name('profile');
        Route::put('/', 'update')->name('profile.update');
        Route::post('avatar', 'uploadAvatar')->name('profile.avatar');
        Route::delete('avatar', 'removeAvatar')->name('profile.avatar.remove');
        Route::put('password', 'changePassword')->name('profile.password');
    });

    //admin routes
    require __DIR__ . '/backend/admin-routes.php';
    require __DIR__ . '/backend/employee-routes.php';

});


require __DIR__ . '/auth.php';
