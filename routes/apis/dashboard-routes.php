<?php

use App\Http\Controllers\Api\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('dashboard', DashboardController::class);
