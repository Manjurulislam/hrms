<?php


use App\Http\Controllers\Backend\DashboardController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;


Route::get('error-logs', [LogViewerController::class, 'index']);


Route::middleware(['auth', 'menu.permission'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    //admin routes
    require __DIR__ . '/backend/admin-routes.php';

});


require __DIR__ . '/auth.php';
